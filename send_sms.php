<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->prepare("SELECT full_name, phone FROM members WHERE id=?");
$stmt->execute([(int)($_GET['member_id'] ?? 0)]);
$member = $stmt->fetch();

$title = 'SMS Statement';
include __DIR__ . '/includes/header.php';
?>
<section class="card max-w-lg">
  <p class="text-sm"><?= $member ? 'Statement for <strong>' . e($member['full_name']) . '</strong> (' . e($member['phone']) . ')' : 'No member selected.' ?></p>
  <div class="mt-3 rounded-lg bg-stone-50 p-3 text-sm text-stone-500">Preview: &ldquo;Bunyoro Cooperative: your savings are UGX 0. Loan balance UGX 0.&rdquo; (placeholder text)</div>
  <a href="members.php" class="btn btn-line mt-4">Back to members</a>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
