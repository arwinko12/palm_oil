<?php
/**
 * Payment — computes the final payout breakdown for a scale ticket using the
 * active pricing settings (workflow §5 step 12):
 *
 *   gross_payment     = net_weight_kg * FFB price per kg
 *   incentive_amount  = gross_payment * incentive_rate
 *   quality_bonus     = gross_payment * quality_bonus_rate
 *   haulage_deduction = (net_weight_kg / 1000) * haulage rate per ton
 *   total_deductions  = haulage_deduction
 *   net_payable       = gross + incentive + quality_bonus - deductions
 */
require_once __DIR__ . '/Model.php';

class Payment extends Model
{
    /** The currently effective pricing row (or null). */
    public function activePricing()
    {
        return $this->one(
            "SELECT * FROM pricing_settings
             WHERE effective_from <= CURDATE()
               AND (effective_to IS NULL OR effective_to >= CURDATE())
             ORDER BY effective_from DESC LIMIT 1"
        );
    }

    /**
     * Compute and persist the payment for a ticket (idempotent upsert).
     * @return array ['ok'=>bool,'error'=>?,'breakdown'=>?]
     */
    public function computeForTicket($ticketId, $userId = null)
    {
        $ticket = $this->one("SELECT * FROM scale_tickets WHERE ticket_id = ?", "i", [$ticketId]);
        if (!$ticket) {
            return ['ok' => false, 'error' => 'Scale ticket not found.', 'breakdown' => null];
        }

        $pricing = $this->activePricing();
        if (!$pricing) {
            return ['ok' => false, 'error' => 'No active pricing settings are configured.', 'breakdown' => null];
        }

        $netKg = (float) $ticket['net_weight'];

        $gross      = round($netKg * (float) $pricing['ffb_price_per_kg'], 2);
        $incentive  = round($gross * (float) $pricing['incentive_rate'], 2);
        $quality    = round($gross * (float) $pricing['quality_bonus_rate'], 2);
        $haulage    = round(($netKg / 1000) * (float) $pricing['haulage_rate_ton'], 2);
        $deductions = round($haulage, 2);
        $netPayable = round($gross + $incentive + $quality - $deductions, 2);

        $this->run(
            "INSERT INTO payment_computations
             (ticket_id, farmer_id, pricing_id, net_weight_kg, gross_payment, incentive_amount,
              quality_bonus, haulage_deduction, total_deductions, net_payable, computed_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                pricing_id = VALUES(pricing_id),
                net_weight_kg = VALUES(net_weight_kg),
                gross_payment = VALUES(gross_payment),
                incentive_amount = VALUES(incentive_amount),
                quality_bonus = VALUES(quality_bonus),
                haulage_deduction = VALUES(haulage_deduction),
                total_deductions = VALUES(total_deductions),
                net_payable = VALUES(net_payable),
                computed_by = VALUES(computed_by),
                updated_at = NOW()",
            "iiidddddddi",
            [
                $ticketId, $ticket['farmer_id'], $pricing['pricing_id'], $netKg,
                $gross, $incentive, $quality, $haulage, $deductions, $netPayable, $userId,
            ]
        );

        return [
            'ok' => true,
            'error' => null,
            'breakdown' => [
                'net_weight_kg'     => $netKg,
                'gross_payment'     => $gross,
                'incentive_amount'  => $incentive,
                'quality_bonus'     => $quality,
                'haulage_deduction' => $haulage,
                'total_deductions'  => $deductions,
                'net_payable'       => $netPayable,
            ],
        ];
    }

    public function forTicket($ticketId)
    {
        return $this->one("SELECT * FROM payment_computations WHERE ticket_id = ?", "i", [$ticketId]);
    }

    /** Every configured pricing period, newest first (admin §46). */
    public function listPricing()
    {
        return $this->all("SELECT * FROM pricing_settings ORDER BY effective_from DESC");
    }

    /**
     * Save a new FFB price / incentive / quality / haulage configuration.
     * Rates are decimals: incentive_rate and quality_bonus_rate are fractions
     * (0.05 = 5%); haulage_rate_ton is currency per ton; ffb_price_per_kg is
     * currency per kg. Closes the previously-open period so only one is active.
     * @return array ['ok'=>bool,'error'=>?,'id'=>?]
     */
    public function savePricing(array $d, $userId = null)
    {
        $ffb   = $d['ffb_price_per_kg'] ?? null;
        $haul  = $d['haulage_rate_ton'] ?? null;
        $inc   = $d['incentive_rate'] ?? null;
        $qual  = $d['quality_bonus_rate'] ?? null;
        $from  = $d['effective_from'] ?? null;

        foreach (['ffb_price_per_kg' => $ffb, 'haulage_rate_ton' => $haul,
                  'incentive_rate' => $inc, 'quality_bonus_rate' => $qual] as $label => $v) {
            if (!is_numeric($v) || $v < 0) {
                return ['ok' => false, 'error' => "A valid non-negative value is required for $label.", 'id' => null];
            }
        }
        if (empty($from) || strtotime($from) === false) {
            return ['ok' => false, 'error' => 'A valid effective-from date is required.', 'id' => null];
        }

        $this->conn->begin_transaction();
        try {
            // Close any currently-open period so there is a single active row.
            $this->run(
                "UPDATE pricing_settings SET effective_to = ? WHERE effective_to IS NULL AND effective_from < ?",
                "ss",
                [date('Y-m-d', strtotime($from . ' -1 day')), $from]
            );
            $this->run(
                "INSERT INTO pricing_settings
                 (ffb_price_per_kg, haulage_rate_ton, incentive_rate, quality_bonus_rate, effective_from, effective_to, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                "dddddds",
                [(float) $ffb, (float) $haul, (float) $inc, (float) $qual, $from,
                 ($d['effective_to'] ?? '') !== '' ? $d['effective_to'] : null, $userId]
            );
            $id = $this->insertId();
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not save pricing: ' . $e->getMessage(), 'id' => null];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->run(
            "INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address)
             VALUES (?, 'update', 'pricing_settings', ?, 'Updated FFB pricing configuration', ?)",
            "iss",
            [$userId, (string) $id, $ip]
        );

        return ['ok' => true, 'error' => null, 'id' => $id];
    }
}
