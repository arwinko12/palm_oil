<?php
/**
 * FarmerProfile — the farmer's own profile and farm information (§3 farmer
 * features, prompt §54-55). Stores full name, contact number, farm location,
 * estimated harvest volume and distance to mill. A farmer may only read and
 * update their own record — every method is keyed on the authenticated
 * user_id / farmer_id, never on an id supplied by the client.
 */
require_once __DIR__ . '/Model.php';

class FarmerProfile extends Model
{
    /** The farmer row for a logged-in user_id (own record only). */
    public function forUser($userId)
    {
        return $this->one("SELECT * FROM farmers WHERE user_id = ?", "i", [$userId]);
    }

    public function get($farmerId)
    {
        return $this->one("SELECT * FROM farmers WHERE farmer_id = ?", "i", [$farmerId]);
    }

    /**
     * Validate + update the farmer's own profile. `fullname` is mirrored to
     * the users row so the login name and profile stay consistent. The last
     * harvest date is intentionally NOT editable here — it is confirmed only
     * through actual completed hauls (see HarvestCycle).
     *
     * @return array ['ok'=>bool,'error'=>?string]
     */
    public function update($farmerId, $userId, array $d)
    {
        $errors = [];

        $fullname = trim($d['fullname'] ?? '');
        $contact  = trim($d['contact_no'] ?? '');
        $location = trim($d['farm_location'] ?? '');
        $tons     = $d['estimated_tons'] ?? null;
        $distance = $d['distance_to_mill'] ?? null;

        if ($fullname === '' || strlen($fullname) > 150) {
            $errors[] = 'Full name is required (max 150 characters).';
        }
        if ($contact === '' || strlen($contact) > 20) {
            $errors[] = 'A contact number is required (max 20 characters).';
        }
        if ($location === '') {
            $errors[] = 'Farm location is required.';
        }
        if (!is_numeric($tons) || $tons < 0) {
            $errors[] = 'Estimated harvest volume must be zero or greater.';
        }
        if (!is_numeric($distance) || $distance < 0) {
            $errors[] = 'Distance to mill must be zero or greater.';
        }

        if ($errors) {
            return ['ok' => false, 'error' => implode(' ', $errors)];
        }

        $this->conn->begin_transaction();
        try {
            $this->run(
                "UPDATE farmers
                 SET fullname = ?, contact_no = ?, farm_location = ?,
                     estimated_tons = ?, distance_to_mill = ?, updated_at = NOW()
                 WHERE farmer_id = ?",
                "ssssdi",
                [$fullname, $contact, $location, $tons, $distance, $farmerId]
            );

            if ($userId) {
                $this->run(
                    "UPDATE users SET full_name = ? WHERE user_id = ?",
                    "si",
                    [$fullname, $userId]
                );
            }

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['ok' => false, 'error' => 'Could not save profile: ' . $e->getMessage()];
        }

        // Keep the live session greeting in sync.
        if (isset($_SESSION['full_name'])) {
            $_SESSION['full_name'] = $fullname;
        }
        if (isset($_SESSION['farmer_name'])) {
            $_SESSION['farmer_name'] = $fullname;
        }

        return ['ok' => true, 'error' => null];
    }
}
