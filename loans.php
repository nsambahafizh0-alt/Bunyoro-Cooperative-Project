<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'issue') {
        $mid = (int)($_POST['member_id'] ?? 0); $principal = (int)($_POST['principal'] ?? 0); $rate = (float)($_POST['interest_rate'] ?? -1);
        $issue = $_POST['issue_date'] ?? ''; $due = $_POST['due_date'] ?? '';
        $ok = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id=? AND status='Active'"); $ok->execute([$mid]);
        if (!$ok->fetchColumn() || $principal <= 0 || $rate < 0 || $rate > 100 || !valid_date($issue) || !valid_date($due) || $due <= $issue) {
            flash('Check the form: active member, amount above zero, rate 0-100%, and a due date after the issue date.', 'err');
        } else {
            $pdo->prepare("INSERT INTO loans (member_id, principal, interest_rate, issue_date, due_date) VALUES (?,?,?,?,?)")->execute([$mid, $principal, $rate, $issue, $due]);
            flash('Loan of ' . ugx($principal) . ' issued.');
        }
    } elseif ($action === 'repay') {
        $lid = (int)($_POST['loan_id'] ?? 0); $amount = (int)($_POST['amount'] ?? 0);
        $date = $_POST['paid_date'] ?? ''; $method = $_POST['method'] ?? ''; $by = trim($_POST['recorded_by'] ?? '');
        $s = $pdo->prepare(LOAN_SQL . " WHERE l.id=?"); $s->execute([$lid]); $row = $s->fetch();
        $loan = $row ? loan_calc($row) : null;
        if (!$loan || $loan['status'] !== 'Active' || $amount <= 0 || $amount > $loan['balance'] || !valid_date($date) || !in_array($method, METHODS, true) || $by === '') {
            flash('Repayment not saved. Pick an active loan and enter an amount no more than the balance' . ($loan ? ' (' . ugx($loan['balance']) . ')' : '') . '.', 'err');
        } else {
            $pdo->prepare("INSERT INTO loan_repayments (loan_id, amount, paid_date, method, recorded_by) VALUES (?,?,?,?,?)")->execute([$lid, $amount, $date, $method, $by]);
            $left = $loan['balance'] - $amount;
            flash('Repayment recorded. ' . ($left > 0 ? 'Balance left: ' . ugx($left) . '.' : 'Loan is now cleared.'));
        }
    }
    redirect('loans.php');
}

$members = $pdo->query("SELECT id, full_name FROM members WHERE status='Active' ORDER BY full_name")->fetchAll();
$officers = $pdo->query("SELECT full_name FROM members WHERE status='Active' AND role <> 'Member' ORDER BY FIELD(role,'Treasurer','Secretary','Chairperson')")->fetchAll(PDO::FETCH_COLUMN);
$loans = array_map('loan_calc', $pdo->query(LOAN_SQL . " ORDER BY l.id DESC")->fetchAll());
$open = array_filter($loans, fn($l) => $l['status'] === 'Active');

$title = 'Loans';
include __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 lg:grid-cols-3">
  <div class="space-y-6">
    <section class="card">
      <h2 class="mb-3 font-head text-lg font-bold text-forest">Issue a loan</h2>
      <form method="post" class="space-y-3"><?= csrf_field() ?><input type="hidden" name="action" value="issue">
        <div><label class="lbl" for="lm">Member</label><select class="inp" id="lm" name="member_id" required><option value="">Choose member</option><?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?></select></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="lbl" for="pr">Principal (UGX)</label><input class="inp" id="pr" name="principal" type="number" inputmode="numeric" min="1000" step="1000" required></div>
          <div><label class="lbl" for="ir">Flat interest %</label><input class="inp" id="ir" name="interest_rate" type="number" min="0" max="100" step="0.5" value="10" required></div>
          <div><label class="lbl" for="id">Issue date</label><input class="inp" id="id" name="issue_date" type="date" value="<?= date('Y-m-d') ?>" required></div>
          <div><label class="lbl" for="dd">Due date</label><input class="inp" id="dd" name="due_date" type="date" value="<?= date('Y-m-d', strtotime('+3 months')) ?>" required></div>
        </div>
        <button class="btn w-full">Issue loan</button>
      </form>
    </section>
    <section class="card">
      <h2 class="mb-3 font-head text-lg font-bold text-forest">Record a repayment</h2>
      <?php if (!$open): ?><p class="text-sm text-stone-500">No active loans to repay.</p><?php else: ?>
      <form method="post" class="space-y-3"><?= csrf_field() ?><input type="hidden" name="action" value="repay">
        <div><label class="lbl" for="rl">Loan</label><select class="inp" id="rl" name="loan_id" required><option value="">Choose loan</option><?php foreach ($open as $l): ?><option value="<?= $l['id'] ?>">#<?= $l['id'] ?> <?= e($l['full_name']) ?> (owes <?= ugx($l['balance']) ?>)</option><?php endforeach; ?></select></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="lbl" for="ra">Amount (UGX)</label><input class="inp" id="ra" name="amount" type="number" inputmode="numeric" min="500" step="500" required></div>
          <div><label class="lbl" for="rd">Date</label><input class="inp" id="rd" name="paid_date" type="date" value="<?= date('Y-m-d') ?>" required></div>
          <div><label class="lbl" for="rm">Method</label><select class="inp" id="rm" name="method"><?php foreach (METHODS as $x): ?><option><?= $x ?></option><?php endforeach; ?></select></div>
          <div><label class="lbl" for="rb">Recorded by</label><input class="inp" id="rb" name="recorded_by" required value="<?= e($officers[0] ?? '') ?>"></div>
        </div>
        <button class="btn w-full">Save repayment</button>
      </form><?php endif; ?>
    </section>
  </div>

  <section class="card lg:col-span-2">
    <h2 class="mb-3 font-head text-lg font-bold text-forest">All loans</h2>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200">
      <thead><tr><th class="th">Member</th><th class="th text-right">Principal</th><th class="th text-right">Total due</th><th class="th text-right">Paid</th><th class="th text-right">Balance</th><th class="th">Due</th><th class="th">Status</th></tr></thead>
      <tbody class="divide-y divide-stone-100"><?php foreach ($loans as $l): ?>
        <tr><td class="td"><div class="font-medium"><?= e($l['full_name']) ?></div></td>
          <td class="td whitespace-nowrap text-right"><?= ugx($l['principal']) ?></td><td class="td whitespace-nowrap text-right"><?= ugx($l['total_due']) ?></td>
          <td class="td whitespace-nowrap text-right"><?= ugx($l['paid']) ?></td><td class="td whitespace-nowrap text-right font-medium"><?= ugx($l['balance']) ?></td>
          <td class="td whitespace-nowrap <?= $l['overdue'] ? 'font-medium text-red-700' : '' ?>"><?= dmy($l['due_date']) ?><?= $l['overdue'] ? '<div class="text-xs">Overdue</div>' : '' ?></td>
          <td class="td"><span class="badge <?= $l['status'] === 'Active' ? 'bg-amber-100 text-amber-900' : 'bg-blue-100 text-blue-800' ?>"><?= $l['status'] ?></span></td></tr>
      <?php endforeach; if (!$loans): ?><tr><td colspan="7" class="td text-stone-500">No loans issued yet.</td></tr><?php endif; ?></tbody></table></div>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
