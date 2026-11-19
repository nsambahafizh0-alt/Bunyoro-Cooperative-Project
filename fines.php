<?php
require_once __DIR__ . '/config/db.php';
$members = ['Asiimwe Grace', 'Byaruhanga Moses', 'Kyomuhendo Sarah', 'Tumusiime Robert', 'Atuhaire Doreen'];
$attendance = [['Asiimwe Grace', 'Present'], ['Byaruhanga Moses', 'Present'], ['Kyomuhendo Sarah', 'Late'], ['Tumusiime Robert', 'Absent'], ['Atuhaire Doreen', 'Late']];
$fines = [['25 Oct 2026', 'Atuhaire Doreen', 'Late arrival', 2000, 'Paid'], ['25 Oct 2026', 'Tumusiime Robert', 'Absent without apology', 5000, 'Unpaid']];
$title = 'Fines & Attendance';
include __DIR__ . '/includes/header.php';
?>
<div class="grid gap-6 lg:grid-cols-3">
  <div class="space-y-6">
    <section class="card">
      <h2 class="mb-3 font-head text-lg font-bold text-forest">Meeting attendance</h2>
      <form onsubmit="event.preventDefault(); alert('Attendance saving is not built yet.');" class="space-y-2">
        <div><label class="lbl" for="md">Meeting date</label><input class="inp" id="md" type="date" value="<?= date('Y-m-d') ?>"></div>
        <?php foreach ($attendance as [$n, $st]): ?>
          <div class="flex items-center justify-between gap-2 text-sm"><span><?= e($n) ?></span>
            <select class="rounded-lg border border-stone-300 px-2 py-1.5"><?php foreach (['Present', 'Late', 'Absent'] as $o): ?><option <?= $o === $st ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?></select></div>
        <?php endforeach; ?>
        <button class="btn w-full">Save attendance</button>
      </form>
    </section>
    <section class="card">
      <h2 class="mb-3 font-head text-lg font-bold text-forest">Record a fine</h2>
      <form onsubmit="event.preventDefault(); alert('Fine recording is not built yet.');" class="space-y-3">
        <div><label class="lbl" for="fm">Member</label><select class="inp" id="fm"><?php foreach ($members as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select></div>
        <div><label class="lbl" for="fr">Reason</label><select class="inp" id="fr"><option>Late arrival</option><option>Absent without apology</option><option>Phone rang during meeting</option><option>Other</option></select></div>
        <div><label class="lbl" for="fa">Amount (UGX)</label><input class="inp" id="fa" type="number" inputmode="numeric" value="2000"></div>
        <button class="btn w-full">Save fine</button>
      </form>
    </section>
  </div>
  <section class="card h-fit lg:col-span-2">
    <h2 class="mb-3 font-head text-lg font-bold text-forest">Recent fines</h2>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200">
      <thead><tr><th class="th">Date</th><th class="th">Member</th><th class="th">Reason</th><th class="th text-right">Amount</th><th class="th">Status</th></tr></thead>
      <tbody class="divide-y divide-stone-100"><?php foreach ($fines as [$d, $n, $r, $a, $s]): ?>
        <tr><td class="td whitespace-nowrap"><?= $d ?></td><td class="td"><?= e($n) ?></td><td class="td"><?= e($r) ?></td><td class="td text-right"><?= ugx($a) ?></td>
          <td class="td"><span class="badge <?= $s === 'Paid' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-900' ?>"><?= $s ?></span></td></tr>
      <?php endforeach; ?></tbody></table></div>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
