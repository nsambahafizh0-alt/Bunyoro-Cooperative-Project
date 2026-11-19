<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $mid = (int)($_POST['member_id'] ?? 0); $amount = (int)($_POST['amount'] ?? 0);
    $date = $_POST['deposit_date'] ?? ''; $method = $_POST['method'] ?? ''; $by = trim($_POST['recorded_by'] ?? '');
    $ok = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id=? AND status='Active'"); $ok->execute([$mid]);
    if (!$ok->fetchColumn() || $amount <= 0 || !valid_date($date) || !in_array($method, METHODS, true) || $by === '') {
        flash('Check the form: pick an active member and enter an amount above zero.', 'err');
    } else {
        $pdo->prepare("INSERT INTO savings (member_id, amount, deposit_date, method, recorded_by) VALUES (?,?,?,?,?)")->execute([$mid, $amount, $date, $method, $by]);
        flash('Deposit of ' . ugx($amount) . ' recorded.');
    }
    redirect('savings.php');
}

$active  = $pdo->query("SELECT id, full_name FROM members WHERE status='Active' ORDER BY full_name")->fetchAll();
$officers = $pdo->query("SELECT full_name FROM members WHERE status='Active' AND role <> 'Member' ORDER BY FIELD(role,'Secretary','Treasurer','Chairperson')")->fetchAll(PDO::FETCH_COLUMN);
$totals  = $pdo->query("SELECT m.id, m.full_name, m.status, COUNT(s.id) AS n, COALESCE(SUM(s.amount),0) AS total FROM members m LEFT JOIN savings s ON s.member_id=m.id GROUP BY m.id ORDER BY total DESC")->fetchAll();
$recent  = $pdo->query("SELECT s.*, m.full_name FROM savings s JOIN members m ON m.id=s.member_id ORDER BY s.deposit_date DESC, s.id DESC LIMIT 15")->fetchAll();
$pool = array_sum(array_column($totals, 'total'));

$title = 'Savings & Contributions';
include __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 lg:grid-cols-3">
  <section class="card h-fit">
    <h2 class="mb-3 font-head text-lg font-bold text-forest">Record a deposit</h2>
    <form method="post" class="space-y-3">
      <?= csrf_field() ?>
      <div><label class="lbl" for="m">Member</label><select class="inp" id="m" name="member_id" required><option value="">Choose member</option><?php foreach ($active as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="lbl" for="a">Amount (UGX)</label><input class="inp" id="a" name="amount" type="number" inputmode="numeric" min="1000" step="500" required placeholder="50000"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="lbl" for="d">Date</label><input class="inp" id="d" name="deposit_date" type="date" value="<?= date('Y-m-d') ?>" required></div>
        <div><label class="lbl" for="p">Payment method</label><select class="inp" id="p" name="method"><?php foreach (METHODS as $x): ?><option><?= $x ?></option><?php endforeach; ?></select></div>
      </div>
      <div><label class="lbl" for="r">Recorded by</label><input class="inp" id="r" name="recorded_by" list="officers" required value="<?= e($officers[0] ?? '') ?>"><datalist id="officers"><?php foreach ($officers as $o): ?><option value="<?= e($o) ?>"><?php endforeach; ?></datalist></div>
      <button class="btn w-full">Save deposit</button>
    </form>
  </section>

  <div class="space-y-6 lg:col-span-2">
    <section class="card">
      <div class="mb-3 flex items-baseline justify-between"><h2 class="font-head text-lg font-bold text-forest">Savings per member</h2><span class="text-sm text-stone-500">Pool: <strong class="text-forest"><?= ugx($pool) ?></strong></span></div>
      <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200">
        <thead><tr><th class="th">Member</th><th class="th text-right">Deposits</th><th class="th text-right">Total saved</th></tr></thead>
        <tbody class="divide-y divide-stone-100"><?php foreach ($totals as $t): ?>
          <tr class="<?= $t['status'] === 'Active' ? '' : 'text-stone-400' ?>"><td class="td"><?= e($t['full_name']) ?><?= $t['status'] === 'Active' ? '' : ' (inactive)' ?></td><td class="td text-right"><?= (int)$t['n'] ?></td><td class="td whitespace-nowrap text-right font-medium"><?= ugx($t['total']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
    </section>
    <section class="card">
      <h2 class="mb-3 font-head text-lg font-bold text-forest">Latest deposits</h2>
      <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200">
        <thead><tr><th class="th">Date</th><th class="th">Member</th><th class="th">Method</th><th class="th">Recorded by</th><th class="th text-right">Amount</th></tr></thead>
        <tbody class="divide-y divide-stone-100"><?php foreach ($recent as $s): ?>
          <tr><td class="td whitespace-nowrap"><?= dmy($s['deposit_date']) ?></td><td class="td"><?= e($s['full_name']) ?></td><td class="td"><?= e($s['method']) ?></td><td class="td"><?= e($s['recorded_by']) ?></td><td class="td whitespace-nowrap text-right"><?= ugx($s['amount']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
    </section>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
