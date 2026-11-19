<?php
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['full_name'] ?? ''); $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? ''; $join = $_POST['join_date'] ?? '';
        if ($name === '' || !preg_match('/^[0-9+ ]{9,16}$/', $phone) || !in_array($role, ROLES, true) || !valid_date($join)) {
            flash('Check the form: name, a valid phone number, role and join date are required.', 'err');
            redirect('members.php' . ($id ? "?edit=$id" : ''));
        }
        if ($id) {
            $pdo->prepare("UPDATE members SET full_name=?, phone=?, role=?, join_date=? WHERE id=?")->execute([$name, $phone, $role, $join, $id]);
        } else {
            $pdo->prepare("INSERT INTO members (full_name, phone, role, join_date) VALUES (?,?,?,?)")->execute([$name, $phone, $role, $join]);
        }
        flash($id ? 'Member updated.' : "$name was added.");
    } elseif ($action === 'toggle') {
        $pdo->prepare("UPDATE members SET status = IF(status='Active','Inactive','Active') WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        flash('Member status changed.');
    }
    redirect('members.php');
}

$edit = null; $view = null;
if (isset($_GET['edit'])) { $s = $pdo->prepare("SELECT * FROM members WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch() ?: null; }
if (isset($_GET['view'])) {
    $s = $pdo->prepare("SELECT m.*, COALESCE((SELECT SUM(amount) FROM savings WHERE member_id=m.id),0) AS total_saved FROM members m WHERE m.id=?");
    $s->execute([(int)$_GET['view']]); $view = $s->fetch() ?: null;
    if ($view) {
        $s = $pdo->prepare("SELECT * FROM savings WHERE member_id=? ORDER BY deposit_date DESC, id DESC LIMIT 5"); $s->execute([$view['id']]); $viewDeposits = $s->fetchAll();
        $s = $pdo->prepare(LOAN_SQL . " WHERE l.member_id=? ORDER BY l.id DESC"); $s->execute([$view['id']]);
        $viewLoans = array_map('loan_calc', $s->fetchAll());
    }
}
$members = $pdo->query("SELECT m.*, COALESCE(SUM(s.amount),0) AS total_saved FROM members m LEFT JOIN savings s ON s.member_id=m.id GROUP BY m.id ORDER BY m.status, m.full_name")->fetchAll();

$title = 'Members';
include __DIR__ . '/includes/header.php';
?>
<?php if ($view): ?>
<section class="card mb-6">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div><h2 class="font-head text-xl font-bold text-forest"><?= e($view['full_name']) ?></h2>
      <p class="text-sm text-stone-600"><?= e($view['role']) ?> &middot; <?= e($view['phone']) ?> &middot; Joined <?= dmy($view['join_date']) ?> &middot; <?= e($view['status']) ?></p></div>
    <div class="flex gap-2">
      <a href="send_sms.php?member_id=<?= $view['id'] ?>" class="btn btn-gold">Send SMS Statement</a>
      <a href="members.php" class="btn btn-line">Close</a>
    </div>
  </div>
  <p class="mt-4 text-sm text-stone-500">Total savings</p>
  <p class="font-head text-2xl font-bold text-forest"><?= ugx($view['total_saved']) ?></p>
  <div class="mt-4 grid gap-6 md:grid-cols-2">
    <div><h3 class="mb-2 text-sm font-semibold">Latest deposits</h3>
      <?php foreach ($viewDeposits as $d): ?><div class="flex justify-between border-b border-stone-100 py-1.5 text-sm"><span><?= dmy($d['deposit_date']) ?> &middot; <?= e($d['method']) ?></span><span><?= ugx($d['amount']) ?></span></div><?php endforeach; ?>
      <?php if (!$viewDeposits): ?><p class="text-sm text-stone-500">No deposits yet.</p><?php endif; ?></div>
    <div><h3 class="mb-2 text-sm font-semibold">Loans</h3>
      <?php foreach ($viewLoans as $l): ?><div class="flex justify-between border-b border-stone-100 py-1.5 text-sm"><span>#<?= $l['id'] ?> &middot; <?= ugx($l['principal']) ?> &middot; <?= $l['status'] ?></span><span>Owes <?= ugx($l['balance']) ?></span></div><?php endforeach; ?>
      <?php if (!$viewLoans): ?><p class="text-sm text-stone-500">No loans taken.</p><?php endif; ?></div>
  </div>
</section>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="card h-fit lg:order-2">
    <h2 class="mb-3 font-head text-lg font-bold text-forest"><?= $edit ? 'Edit member' : 'Add member' ?></h2>
    <form method="post" class="space-y-3">
      <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div><label class="lbl" for="fn">Full name</label><input class="inp" id="fn" name="full_name" required value="<?= e($edit['full_name'] ?? '') ?>"></div>
      <div><label class="lbl" for="ph">Phone number</label><input class="inp" id="ph" name="phone" type="tel" inputmode="tel" placeholder="0772 000 000" required value="<?= e($edit['phone'] ?? '') ?>"></div>
      <div><label class="lbl" for="ro">Role</label><select class="inp" id="ro" name="role"><?php foreach (ROLES as $r): ?><option <?= ($edit['role'] ?? 'Member') === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></div>
      <div><label class="lbl" for="jd">Join date</label><input class="inp" id="jd" name="join_date" type="date" required value="<?= e($edit['join_date'] ?? date('Y-m-d')) ?>"></div>
      <div class="flex gap-2"><button class="btn flex-1"><?= $edit ? 'Save changes' : 'Add member' ?></button><?php if ($edit): ?><a href="members.php" class="btn btn-line">Cancel</a><?php endif; ?></div>
    </form>
  </section>

  <section class="card lg:col-span-2 lg:order-1">
    <h2 class="mb-3 font-head text-lg font-bold text-forest">All members (<?= count($members) ?>)</h2>
    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-stone-200">
      <thead><tr><th class="th">Name</th><th class="th">Role</th><th class="th text-right">Savings</th><th class="th">Status</th><th class="th">Actions</th></tr></thead>
      <tbody class="divide-y divide-stone-100">
      <?php foreach ($members as $m): $on = $m['status'] === 'Active'; ?>
        <tr class="<?= $on ? '' : 'text-stone-400' ?>">
          <td class="td"><div class="font-medium"><?= e($m['full_name']) ?></div><div class="text-xs"><?= e($m['phone']) ?></div></td>
          <td class="td"><?= e($m['role']) ?></td>
          <td class="td whitespace-nowrap text-right"><?= ugx($m['total_saved']) ?></td>
          <td class="td"><span class="badge <?= $on ? 'bg-blue-100 text-blue-800' : 'bg-stone-200 text-stone-600' ?>"><?= e($m['status']) ?></span></td>
          <td class="td"><div class="flex flex-wrap gap-x-3 gap-y-1 text-sm font-medium">
            <a class="text-forest underline" href="members.php?view=<?= $m['id'] ?>">View</a>
            <a class="text-forest underline" href="members.php?edit=<?= $m['id'] ?>">Edit</a>
            <form method="post" data-confirm="<?= $on ? 'Mark this member Inactive?' : 'Mark this member Active?' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="text-gold-dark underline"><?= $on ? 'Deactivate' : 'Activate' ?></button></form>
            <a class="text-stone-500 underline" href="send_sms.php?member_id=<?= $m['id'] ?>" title="Coming soon">Send SMS Statement</a>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
