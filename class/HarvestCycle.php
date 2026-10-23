<?php
/**
 * HarvestCycle — configurable harvest-cycle scheduling (workflow §6).
 *
 * The next expected harvest date is derived from the last CONFIRMED harvest
 * date plus the configurable cycle length (default 20 days). The cycle is
 * never treated as proof that harvesting happened — recalculation only occurs
 * when an actual harvest date is confirmed.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';

class HarvestCycle extends Model
{
    private $notifier;

    public function __construct()
    {
        parent::__construct();
        $this->notifier = new Notification();
    }

    public function cycleDays()
    {
        $v = $this->scalar("SELECT setting_value FROM system_settings WHERE setting_key = 'harvest_cycle_days'");
        return $v !== null ? max(1, (int) $v) : 20;
    }

    private function reminderDays()
    {
        $v = $this->scalar("SELECT setting_value FROM system_settings WHERE setting_key = 'reminder_days_before'");
        return $v !== null ? max(0, (int) $v) : 3;
    }

    /** Pure date math: last confirmed date + cycle days. */
    public function computeNextDate($lastConfirmedDate)
    {
        $ts = strtotime($lastConfirmedDate);
        if ($ts === false) {
            return null;
        }
        $days = $this->cycleDays();
        return date('Y-m-d', strtotime("+$days days", $ts));
    }

    /**
     * Record an actual harvest and recompute the next expected date.
     * Called by authorized users and automatically on delivery completion.
     */
    public function confirm($farmerId, $actualDate, $userId = null)
    {
        if (empty($actualDate) || strtotime($actualDate) === false) {
            return ['ok' => false, 'error' => 'A valid actual harvest date is required.'];
        }

        $days = $this->cycleDays();
        $next = date('Y-m-d', strtotime($actualDate . " +$days days"));

        $this->conn->begin_transaction();
        try {
            // farmers.last_harvest_date tracks the latest confirmed harvest.
            $this->run(
                "UPDATE farmers SET last_harvest_date = ?, updated_at = NOW() WHERE farmer_id = ?",
                "si",
                [$actualDate, $farmerId]
            );

            $this->run(
                "INSERT INTO harvest_cycles
                 (farmer_id, previous_harvest_date, next_harvest_date, cycle_days, is_confirmed, confirmed_by)
                 VALUES (?, ?, ?, ?, 1, ?)",
                "issss",
                [$farmerId, $actualDate, $next, $days, $userId]
            );

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not record harvest: ' . $e->getMessage()];
        }

        return ['ok' => true, 'error' => null, 'next_harvest_date' => $next];
    }

    /** Latest cycle for a farmer (has next_harvest_date been computed). */
    public function current($farmerId)
    {
        return $this->one(
            "SELECT * FROM harvest_cycles WHERE farmer_id = ?
             ORDER BY next_harvest_date DESC LIMIT 1",
            "i",
            [$farmerId]
        );
    }

    /** All confirmed harvest cycles for a farmer, newest first. */
    public function history($farmerId)
    {
        return $this->all(
            "SELECT * FROM harvest_cycles WHERE farmer_id = ?
             ORDER BY previous_harvest_date DESC",
            "i",
            [$farmerId]
        );
    }

    /**
     * Next harvest date for the farmer dashboard. Falls back to the farmer's
     * stored last_harvest_date if no cycle row exists yet.
     */
    public function nextHarvestDate($farmerId)
    {
        $cycle = $this->current($farmerId);
        if ($cycle) {
            return $cycle['next_harvest_date'];
        }
        $last = $this->scalar("SELECT last_harvest_date FROM farmers WHERE farmer_id = ?", "i", [$farmerId]);
        return $last ? $this->computeNextDate($last) : null;
    }

    /** Authorized correction of a farmer's last/next harvest dates. */
    public function correct($farmerId, $newActualDate, $userId)
    {
        // A correction is a confirmation of the operational record.
        return $this->confirm($farmerId, $newActualDate, $userId);
    }

    /**
     * Generate reminders for farmers whose next harvest date falls within the
     * reminder window. Returns how many reminders were created.
     */
    public function generateReminders()
    {
        $before = $this->reminderDays();
        $today = date('Y-m-d');
        $until = date('Y-m-d', strtotime("+$before days"));

        $rows = $this->all(
            "SELECT c.farmer_id, c.next_harvest_date, f.user_id
             FROM harvest_cycles c
             JOIN farmers f ON f.farmer_id = c.farmer_id
             WHERE c.is_confirmed = 1
               AND c.next_harvest_date BETWEEN ? AND ?",
            "ss",
            [$today, $until]
        );

        $count = 0;
        foreach ($rows as $r) {
            if (!$r['user_id']) {
                continue;
            }
            $this->notifier->send(
                $r['user_id'],
                'Harvest reminder',
                "Your next harvest is expected on {$r['next_harvest_date']}. Consider submitting a hauling request.",
                'warning'
            );
            $count++;
        }
        return $count;
    }
}
