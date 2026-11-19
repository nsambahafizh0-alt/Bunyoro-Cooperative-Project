<?php
require_once __DIR__ . '/config/db.php';

$savings  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM savings")->fetchColumn();
$repaid   = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM loan_repayments")->fetchColumn();
$issued   = (float)$pdo->query("SELECT COALESCE(SUM(principal),0) FROM loans")->fetchColumn();
$fines    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM fines WHERE status='Paid'")->fetchColumn();

$activeCount = 0; $activePrincipal = 0.0; $outstanding = 0.0;
foreach ($pdo->query(LOAN_SQL) as $row) {
    $l = loan_calc($row);
    if ($l['status'] === 'Active') { $activeCount++; $activePrincipal += (float)$l['principal']; $outstanding += $l['balance']; }
}
$cash = $savings + $repaid + $fines - $issued;

$recent = $pdo->query("
  (SELECT s.deposit_date AS d, s.id, m.full_name, 'Savings deposit' AS type, s.amount, 'in' AS dir FROM savings s JOIN members m ON m.id=s.member_id)
  UNION ALL
  (SELECT l.issue_date, l.id, m.full_name, 'Loan issued', l.principal, 'out' FROM loans l JOIN members m ON m.id=l.member_id)
  UNION ALL
  (SELECT r.paid_date, r.id, m.full_name, 'Loan repayment', r.amount, 'in' FROM loan_repayments r JOIN loans l ON l.id=r.loan_id JOIN members m ON m.id=l.member_id)
  UNION ALL
  (SELECT f.fine_date, f.id, m.full_name, 'Fine paid', f.amount, 'in' FROM fines f JOIN members m ON m.id=f.member_id WHERE f.status='Paid')
  ORDER BY d DESC, id DESC LIMIT 10")->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
  <div class="rounded-xl bg-blue-800 p-5 text-white shadow-sm sm:col-span-2 lg:col-span-1">
    <div class="text-sm text-blue-100">Total savings pool</div>
    <div class="mt-1 font-head text-2xl font-bold"><?= ugx($savings) ?></div>
  </div>
  <div class="card"><div class="text-sm text-stone-500">Active loans issued</div>
    <div class="mt-1 font-head text-2xl font-bold text-blue-800"><?= ugx($activePrincipal) ?></div>
    <div class="mt-1 text-xs text-stone-500"><?= $activeCount ?> active &middot; <?= ugx($outstanding) ?> still owed</div></div>
  <div class="card"><div class="text-sm text-stone-500">Cash on hand</div>
    <div class="mt-1 font-head text-2xl font-bold text-blue-800"><?= ugx($cash) ?></div></div>
  <div class="card"><div class="text-sm text-stone-500">Fines collected</div>
    <div class="mt-1 font-head text-2xl font-bold text-gold-dark"><?= ugx($fines) ?></div></div>
</section>

<section class="card">
  <h2 class="mb-3 font-head text-lg font-bold text-blue-900">Recent transactions</h2>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-stone-200">
      <thead><tr><th class="th">Date</th><th class="th">Member</th><th class="th">Type</th><th class="th text-right">Amount</th></tr></thead>
      <tbody class="divide-y divide-stone-100">
      <?php foreach ($recent as $t): ?>
        <tr><td class="td whitespace-nowrap"><?= dmy($t['d']) ?></td><td class="td"><?= e($t['full_name']) ?></td><td class="td"><?= e($t['type']) ?></td>
          <td class="td whitespace-nowrap text-right font-medium <?= $t['dir'] === 'out' ? 'text-red-700' : 'text-blue-800' ?>"><?= $t['dir'] === 'out' ? '&minus; ' : '+ ' ?><?= ugx($t['amount']) ?></td></tr>
      <?php endforeach; if (!$recent): ?><tr><td colspan="4" class="td text-stone-500">No transactions yet. Record the first deposit on the Savings page.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
