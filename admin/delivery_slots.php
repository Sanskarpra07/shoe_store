<?php
session_start();
$page_title = 'Delivery Slots';
$current_page = 'delivery_slots';
require_once 'includes/header.php';

$errors = [];
$success = "";

// Actions: toggle active / delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'] ?: 0;

    if ($_GET['action'] === 'toggle') {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM delivery_slots WHERE id = $id"));
        if ($r) {
            $new = $r['is_active'] ? 0 : 1;
            mysqli_query($conn, "UPDATE delivery_slots SET is_active = $new WHERE id = $id");
            $success = $new ? "Slot activated." : "Slot deactivated.";
        }
    } elseif ($_GET['action'] === 'delete') {
        mysqli_query($conn, "DELETE FROM delivery_slots WHERE id = $id");
        $success = "Delivery slot deleted.";
    }
    header("Location: delivery_slots.php");
    exit();
}

// Load a slot into the form for editing
$edit_slot = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'] ?: 0;
    $edit_slot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM delivery_slots WHERE id = $edit_id"));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_name = trim($_POST['slot_name'] ?? '');
    $slot_time = trim($_POST['slot_time'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $slot_id   = (int)($_POST['slot_id'] ?? 0);

    if (empty($slot_name)) $errors[] = "Slot name is required (e.g. Morning).";
    if (empty($slot_time)) $errors[] = "Slot time is required (e.g. 9 AM - 11 AM).";

    if (empty($errors)) {
        if ($slot_id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE delivery_slots SET slot_name = ?, slot_time = ?, is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssii", $slot_name, $slot_time, $is_active, $slot_id);
            mysqli_stmt_execute($stmt);
            $success = "Delivery slot updated.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO delivery_slots (slot_name, slot_time, is_active) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssi", $slot_name, $slot_time, $is_active);
            mysqli_stmt_execute($stmt);
            $success = "Delivery slot added.";
        }
        header("Location: delivery_slots.php");
        exit();
    }
}

$slots = mysqli_query($conn, "SELECT * FROM delivery_slots ORDER BY slot_time ASC");
?>

<div class="page-header">
    <div>
        <h2>Delivery Slots</h2>
        <p class="page-sub">Define the time slots customers can choose at checkout.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="msg-error">
        <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="msg-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="split">
    <div class="form-box">
        <h3><i class="bi bi-clock"></i> <?= $edit_slot ? 'Edit Slot' : 'Add New Slot' ?></h3>
        <form method="POST" action="delivery_slots.php">
            <input type="hidden" name="slot_id" value="<?= $edit_slot['id'] ?? 0 ?>">
            <div class="form-group">
                <label>Slot Name *</label>
                <input type="text" name="slot_name" value="<?= htmlspecialchars($edit_slot['slot_name'] ?? '') ?>"
                       placeholder="e.g. Morning" required>
            </div>
            <div class="form-group">
                <label>Slot Time *</label>
                <input type="text" name="slot_time" value="<?= htmlspecialchars($edit_slot['slot_time'] ?? '') ?>"
                       placeholder="e.g. 9 AM - 11 AM" required>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; font-weight:600;">
                    <input type="checkbox" name="is_active" style="width:auto;" <?= (!isset($edit_slot) || $edit_slot['is_active']) ? 'checked' : '' ?>>
                    Active (shown at checkout)
                </label>
            </div>
            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn" style="flex:1;"><i class="bi bi-check-lg"></i> <?= $edit_slot ? 'Update Slot' : 'Add Slot' ?></button>
                <?php if ($edit_slot): ?>
                    <a href="delivery_slots.php" class="btn btn-gray"><i class="bi bi-x-lg"></i> Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div>
        <h3 class="section-title">Available Slots</h3>
        <table class="table">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php $sno = 1; while ($s = mysqli_fetch_assoc($slots)): ?>
            <tr>
                <td class="center"><?= $sno++ ?></td>
                <td><strong><?= htmlspecialchars($s['slot_name']) ?></strong></td>
                <td class="center"><?= htmlspecialchars($s['slot_time']) ?></td>
                <td class="center">
                    <?= $s['is_active']
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-secondary">Inactive</span>' ?>
                </td>
                <td class="center">
                    <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                        <a class="btn btn-small" href="delivery_slots.php?edit=<?= $s['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                        <a class="btn btn-gray btn-small"
                           href="delivery_slots.php?action=toggle&id=<?= $s['id'] ?>">
                            <i class="bi bi-power"></i> <?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </a>
                        <a class="btn btn-red btn-small" href="delivery_slots.php?action=delete&id=<?= $s['id'] ?>"
                           onclick="return confirm('Delete this delivery slot?')"><i class="bi bi-trash"></i> Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($slots) === 0): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i>No delivery slots added yet.</div></td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>