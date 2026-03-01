<?php
/**
 * Admin Reservations Dashboard
 * Mezzanine Restaurant
 */

require_once 'db_connect.php';

// ── Handle status updates ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $id     = intval($_POST['id']);
    $type   = $_POST['type']; // 'reservation' or 'business'

    if ($_POST['action'] === 'update_status') {
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        if ($type === 'reservation') {
            $conn->query("UPDATE reservations SET status='$status' WHERE id=$id");
        } else {
            $conn->query("UPDATE business_inquiries SET status='$status' WHERE id=$id");
        }
    } elseif ($_POST['action'] === 'delete') {
        $table = ($type === 'reservation') ? 'reservations' : 'business_inquiries';
        $conn->query("DELETE FROM $table WHERE id=$id");
    }

    // Redirect to avoid form re-submission
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=" . ($_POST['tab'] ?? 'reservations'));
    exit;
}

// ── Filters & Pagination ──────────────────────────────────────────────────
$tab          = $_GET['tab'] ?? 'reservations';
$search       = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$date_filter  = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$page         = max(1, intval($_GET['page'] ?? 1));
$per_page     = 10;
$offset       = ($page - 1) * $per_page;

// ── Fetch Table Reservations ──────────────────────────────────────────────
$res_where = "WHERE 1=1";
if ($search)        $res_where .= " AND (customer_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
if ($status_filter) $res_where .= " AND status='$status_filter'";
if ($date_filter)   $res_where .= " AND reservation_date='$date_filter'";

$res_total  = $conn->query("SELECT COUNT(*) FROM reservations $res_where")->fetch_row()[0];
$res_result = $conn->query("SELECT * FROM reservations $res_where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

// ── Fetch Business Inquiries ──────────────────────────────────────────────
$biz_where = "WHERE 1=1";
if ($search)        $biz_where .= " AND (company_name LIKE '%$search%' OR contact_person LIKE '%$search%' OR email LIKE '%$search%')";
if ($status_filter) $biz_where .= " AND status='$status_filter'";
if ($date_filter)   $biz_where .= " AND event_date='$date_filter'";

$biz_total  = $conn->query("SELECT COUNT(*) FROM business_inquiries $biz_where")->fetch_row()[0];
$biz_result = $conn->query("SELECT * FROM business_inquiries $biz_where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");

// ── Summary Stats ─────────────────────────────────────────────────────────
$stats = [
    'res_total'    => $conn->query("SELECT COUNT(*) FROM reservations")->fetch_row()[0],
    'res_pending'  => $conn->query("SELECT COUNT(*) FROM reservations WHERE status='pending'")->fetch_row()[0],
    'res_confirmed'=> $conn->query("SELECT COUNT(*) FROM reservations WHERE status='confirmed'")->fetch_row()[0],
    'biz_total'    => $conn->query("SELECT COUNT(*) FROM business_inquiries")->fetch_row()[0],
    'biz_new'      => $conn->query("SELECT COUNT(*) FROM business_inquiries WHERE status='new'")->fetch_row()[0],
    'today_res'    => $conn->query("SELECT COUNT(*) FROM reservations WHERE reservation_date=CURDATE()")->fetch_row()[0],
];

// Helper
function statusBadge($status) {
    $map = [
        'pending'   => '#D6A34F',
        'confirmed' => '#4CAF50',
        'cancelled' => '#e53935',
        'completed' => '#7C1309',
        'new'       => '#1976D2',
        'contacted' => '#9C27B0',
        'closed'    => '#555',
    ];
    $color = $map[$status] ?? '#888';
    return "<span style='background:$color;color:#fff;padding:3px 10px;border-radius:20px;font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;font-weight:700;'>$status</span>";
}

$total_pages = ceil(($tab === 'reservations' ? $res_total : $biz_total) / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservations Dashboard | Mezzanine Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #7C1309;
    --accent:  #D6A34F;
    --light:   #FFF7EF;
    --white:   #FFFFFF;
    --dark:    #1a1a1a;
    --gray:    #f5f0ea;
    --border:  #e8ddd4;
    --serif:   'Playfair Display', serif;
    --sans:    'Lato', sans-serif;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:var(--sans); background:var(--light); color:var(--dark); min-height:100vh; }

/* ─── HEADER ─── */
.admin-header {
    background: var(--primary);
    padding: 1rem 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.admin-header .brand { font-family:var(--serif); color:#fff; font-size:1.4rem; letter-spacing:0.2em; text-transform:uppercase; }
.admin-header .brand span { color:var(--accent); }
.admin-header .subtitle { font-size:0.7rem; color:rgba(255,255,255,0.6); letter-spacing:0.15em; text-transform:uppercase; margin-top:2px; }
.header-right a { color:rgba(255,255,255,0.7); font-size:0.75rem; text-decoration:none; letter-spacing:0.1em; text-transform:uppercase; border:1px solid rgba(255,255,255,0.3); padding:0.4rem 1rem; border-radius:2px; transition:all 0.3s; }
.header-right a:hover { background:rgba(255,255,255,0.1); color:#fff; }

/* ─── LAYOUT ─── */
.container { max-width:1400px; margin:0 auto; padding:2rem 2.5rem; }

/* ─── STAT CARDS ─── */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:2rem; }
.stat-card {
    background:var(--white);
    border-radius:4px;
    padding:1.4rem 1.5rem;
    border-left:4px solid var(--accent);
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.stat-card.primary { border-left-color:var(--primary); }
.stat-card.green   { border-left-color:#4CAF50; }
.stat-card.blue    { border-left-color:#1976D2; }
.stat-num { font-family:var(--serif); font-size:2.2rem; color:var(--primary); line-height:1; }
.stat-label { font-size:0.7rem; text-transform:uppercase; letter-spacing:0.12em; color:#888; margin-top:0.3rem; }

/* ─── TABS ─── */
.tabs { display:flex; gap:0; margin-bottom:0; border-bottom:2px solid var(--border); }
.tab-btn {
    padding:0.8rem 2rem;
    font-family:var(--sans);
    font-size:0.75rem;
    letter-spacing:0.15em;
    text-transform:uppercase;
    font-weight:700;
    background:none;
    border:none;
    cursor:pointer;
    color:#888;
    border-bottom:2px solid transparent;
    margin-bottom:-2px;
    transition:all 0.3s;
    text-decoration:none;
    display:inline-block;
}
.tab-btn.active, .tab-btn:hover { color:var(--primary); border-bottom-color:var(--primary); }

/* ─── FILTER BAR ─── */
.filter-bar {
    background:var(--white);
    padding:1rem 1.5rem;
    border-radius:4px;
    display:flex;
    gap:0.8rem;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:1.2rem;
    box-shadow:0 1px 4px rgba(0,0,0,0.04);
}
.filter-bar input, .filter-bar select {
    border:1px solid var(--border);
    background:var(--light);
    padding:0.5rem 0.9rem;
    font-family:var(--sans);
    font-size:0.8rem;
    color:var(--dark);
    border-radius:2px;
    outline:none;
    transition:border 0.3s;
}
.filter-bar input:focus, .filter-bar select:focus { border-color:var(--accent); }
.filter-bar input[type="text"] { min-width:220px; }
.btn { padding:0.5rem 1.2rem; font-size:0.75rem; letter-spacing:0.1em; text-transform:uppercase; font-weight:700; border:none; cursor:pointer; border-radius:2px; transition:all 0.3s; font-family:var(--sans); }
.btn-primary { background:var(--primary); color:#fff; }
.btn-primary:hover { background:#9b1a0c; }
.btn-outline { background:none; border:1px solid var(--border); color:#888; }
.btn-outline:hover { border-color:var(--primary); color:var(--primary); }
.btn-sm { padding:0.3rem 0.8rem; font-size:0.68rem; }
.btn-danger { background:#e53935; color:#fff; }
.btn-danger:hover { background:#c62828; }
.btn-success { background:#4CAF50; color:#fff; }
.btn-success:hover { background:#388E3C; }

/* ─── TABLE ─── */
.table-wrap {
    background:var(--white);
    border-radius:4px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.table-header {
    padding:1rem 1.5rem;
    border-bottom:1px solid var(--border);
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.table-title { font-family:var(--serif); font-size:1.1rem; color:var(--primary); }
.record-count { font-size:0.75rem; color:#888; }
table { width:100%; border-collapse:collapse; }
thead { background:var(--gray); }
th {
    padding:0.75rem 1rem;
    text-align:left;
    font-size:0.68rem;
    text-transform:uppercase;
    letter-spacing:0.12em;
    color:#888;
    font-weight:700;
    white-space:nowrap;
}
td {
    padding:0.85rem 1rem;
    border-bottom:1px solid var(--border);
    font-size:0.82rem;
    vertical-align:middle;
}
tr:last-child td { border-bottom:none; }
tr:hover td { background:#fffaf5; }
.name-cell { font-weight:700; color:var(--dark); }
.sub-info { font-size:0.72rem; color:#888; margin-top:2px; }
.action-cell { white-space:nowrap; }
.action-cell form { display:inline; }

/* ─── STATUS FORM ─── */
.status-select { border:1px solid var(--border); background:var(--light); padding:0.25rem 0.5rem; font-size:0.72rem; border-radius:2px; font-family:var(--sans); cursor:pointer; }

/* ─── PAGINATION ─── */
.pagination { display:flex; gap:0.4rem; justify-content:center; margin-top:1.5rem; }
.pagination a, .pagination span {
    padding:0.45rem 0.9rem;
    border:1px solid var(--border);
    font-size:0.75rem;
    border-radius:2px;
    text-decoration:none;
    color:var(--dark);
    transition:all 0.2s;
}
.pagination a:hover { border-color:var(--primary); color:var(--primary); }
.pagination .current { background:var(--primary); color:#fff; border-color:var(--primary); }

/* ─── EMPTY STATE ─── */
.empty { text-align:center; padding:3rem; color:#aaa; }
.empty-icon { font-size:2.5rem; margin-bottom:0.5rem; }
.empty p { font-size:0.85rem; }

/* ─── MODAL ─── */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:500; align-items:center; justify-content:center; }
.modal-bg.open { display:flex; }
.modal {
    background:var(--white);
    border-radius:4px;
    padding:2rem;
    max-width:560px;
    width:90%;
    max-height:85vh;
    overflow-y:auto;
    position:relative;
    box-shadow:0 10px 40px rgba(0,0,0,0.2);
}
.modal-close { position:absolute; top:1rem; right:1rem; background:none; border:none; font-size:1.3rem; cursor:pointer; color:#aaa; }
.modal-close:hover { color:var(--primary); }
.modal h3 { font-family:var(--serif); color:var(--primary); margin-bottom:1.2rem; font-size:1.3rem; }
.detail-row { display:flex; gap:1rem; margin-bottom:0.8rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border); }
.detail-row:last-child { border-bottom:none; margin-bottom:0; }
.detail-label { font-size:0.7rem; text-transform:uppercase; letter-spacing:0.1em; color:#888; min-width:130px; padding-top:2px; }
.detail-value { font-size:0.85rem; color:var(--dark); flex:1; }
.divider { border:none; border-top:2px solid var(--accent); margin:1.5rem 0; opacity:0.3; }

/* ─── RESPONSIVE ─── */
@media (max-width:900px) {
    .container { padding:1rem; }
    table { display:block; overflow-x:auto; white-space:nowrap; }
    .filter-bar { flex-direction:column; align-items:stretch; }
    .filter-bar input[type="text"] { min-width:auto; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header class="admin-header">
    <div>
        <div class="brand">Mezz<span>anine</span></div>
        <div class="subtitle">Reservations Dashboard</div>
    </div>
    <div class="header-right">
        <a href="MezzanineMain.html">← Back to Site</a>
    </div>
</header>

<div class="container">

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-num"><?= $stats['res_total'] ?></div>
            <div class="stat-label">Total Reservations</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $stats['res_pending'] ?></div>
            <div class="stat-label">Pending Tables</div>
        </div>
        <div class="stat-card green">
            <div class="stat-num"><?= $stats['res_confirmed'] ?></div>
            <div class="stat-label">Confirmed Tables</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $stats['today_res'] ?></div>
            <div class="stat-label">Today's Tables</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-num"><?= $stats['biz_total'] ?></div>
            <div class="stat-label">Business Inquiries</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $stats['biz_new'] ?></div>
            <div class="stat-label">New Inquiries</div>
        </div>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tab=reservations" class="tab-btn <?= $tab === 'reservations' ? 'active' : '' ?>">Table Reservations</a>
        <a href="?tab=business" class="tab-btn <?= $tab === 'business' ? 'active' : '' ?>">Business Inquiries</a>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" action="">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="Search by name, email, phone…" value="<?= htmlspecialchars($search) ?>">
            <?php if ($tab === 'reservations'): ?>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach(['pending','confirmed','cancelled','completed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?php else: ?>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach(['new','contacted','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?tab=<?= $tab ?>" class="btn btn-outline">Clear</a>
        </div>
    </form>

    <!-- ════════════════ TABLE RESERVATIONS ════════════════ -->
    <?php if ($tab === 'reservations'): ?>
    <div class="table-wrap">
        <div class="table-header">
            <div class="table-title">Table Reservations</div>
            <div class="record-count"><?= $res_total ?> record<?= $res_total != 1 ? 's' : '' ?> found</div>
        </div>
        <?php if ($res_result && $res_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guest</th>
                    <th>Contact</th>
                    <th>Date & Time</th>
                    <th>Guests</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $res_result->fetch_assoc()): ?>
                <tr>
                    <td style="color:#ccc;font-size:0.75rem;"><?= $row['id'] ?></td>
                    <td>
                        <div class="name-cell"><?= htmlspecialchars($row['customer_name']) ?></div>
                        <div class="sub-info"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <div><?= date('M j, Y', strtotime($row['reservation_date'])) ?></div>
                        <div class="sub-info"><?= date('g:i A', strtotime($row['reservation_time'])) ?></div>
                    </td>
                    <td style="text-align:center;"><?= $row['number_of_guests'] ?></td>
                    <td><?= statusBadge($row['status']) ?></td>
                    <td style="white-space:nowrap;font-size:0.75rem;color:#aaa;"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="action-cell">
                        <!-- View Details -->
                        <button class="btn btn-outline btn-sm"
                            onclick="showDetail(
                                '<?= addslashes(htmlspecialchars($row['customer_name'])) ?>',
                                [
                                    ['Name', '<?= addslashes(htmlspecialchars($row['customer_name'])) ?>'],
                                    ['Email', '<?= addslashes(htmlspecialchars($row['email'])) ?>'],
                                    ['Phone', '<?= addslashes(htmlspecialchars($row['phone'])) ?>'],
                                    ['Date', '<?= date('F j, Y', strtotime($row['reservation_date'])) ?>'],
                                    ['Time', '<?= date('g:i A', strtotime($row['reservation_time'])) ?>'],
                                    ['Guests', '<?= $row['number_of_guests'] ?>'],
                                    ['Status', '<?= ucfirst($row['status']) ?>'],
                                    ['Special Requests', '<?= addslashes(htmlspecialchars($row['special_requests'] ?: '—')) ?>'],
                                    ['Submitted', '<?= date('F j, Y g:i A', strtotime($row['created_at'])) ?>']
                                ]
                            )">View</button>
                        <!-- Update Status -->
                        <form method="POST" style="display:inline-block;margin-left:4px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="type" value="reservation">
                            <input type="hidden" name="tab" value="reservations">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <?php foreach(['pending','confirmed','cancelled','completed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <!-- Delete -->
                        <form method="POST" style="display:inline-block;margin-left:4px;" onsubmit="return confirm('Delete this reservation?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="type" value="reservation">
                            <input type="hidden" name="tab" value="reservations">
                            <button type="submit" class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">
            <div class="empty-icon">🍽️</div>
            <p>No reservations found.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ════════════════ BUSINESS INQUIRIES ════════════════ -->
    <?php else: ?>
    <div class="table-wrap">
        <div class="table-header">
            <div class="table-title">Business Inquiries</div>
            <div class="record-count"><?= $biz_total ?> record<?= $biz_total != 1 ? 's' : '' ?> found</div>
        </div>
        <?php if ($biz_result && $biz_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company / Contact</th>
                    <th>Email & Phone</th>
                    <th>Event</th>
                    <th>Attendees</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $biz_result->fetch_assoc()): ?>
                <tr>
                    <td style="color:#ccc;font-size:0.75rem;"><?= $row['id'] ?></td>
                    <td>
                        <div class="name-cell"><?= htmlspecialchars($row['company_name']) ?></div>
                        <div class="sub-info"><?= htmlspecialchars($row['contact_person']) ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($row['email']) ?></div>
                        <div class="sub-info"><?= htmlspecialchars($row['phone']) ?></div>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($row['event_type']) ?></div>
                        <?php if ($row['event_date']): ?>
                        <div class="sub-info"><?= date('M j, Y', strtotime($row['event_date'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><?= $row['number_of_attendees'] ?></td>
                    <td><?= statusBadge($row['status']) ?></td>
                    <td style="white-space:nowrap;font-size:0.75rem;color:#aaa;"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td class="action-cell">
                        <!-- View Details -->
                        <button class="btn btn-outline btn-sm"
                            onclick="showDetail(
                                '<?= addslashes(htmlspecialchars($row['company_name'])) ?>',
                                [
                                    ['Company', '<?= addslashes(htmlspecialchars($row['company_name'])) ?>'],
                                    ['Contact Person', '<?= addslashes(htmlspecialchars($row['contact_person'])) ?>'],
                                    ['Email', '<?= addslashes(htmlspecialchars($row['email'])) ?>'],
                                    ['Phone', '<?= addslashes(htmlspecialchars($row['phone'])) ?>'],
                                    ['Event Type', '<?= addslashes(htmlspecialchars($row['event_type'])) ?>'],
                                    ['Event Date', '<?= $row['event_date'] ? date('F j, Y', strtotime($row['event_date'])) : '—' ?>'],
                                    ['Attendees', '<?= $row['number_of_attendees'] ?>'],
                                    ['Status', '<?= ucfirst($row['status']) ?>'],
                                    ['Requirements', '<?= addslashes(htmlspecialchars($row['requirements'] ?: '—')) ?>'],
                                    ['Submitted', '<?= date('F j, Y g:i A', strtotime($row['created_at'])) ?>']
                                ]
                            )">View</button>
                        <!-- Update Status -->
                        <form method="POST" style="display:inline-block;margin-left:4px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="type" value="business">
                            <input type="hidden" name="tab" value="business">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <?php foreach(['new','contacted','closed'] as $s): ?>
                                <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <!-- Delete -->
                        <form method="POST" style="display:inline-block;margin-left:4px;" onsubmit="return confirm('Delete this inquiry?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="type" value="business">
                            <input type="hidden" name="tab" value="business">
                            <button type="submit" class="btn btn-danger btn-sm">✕</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">
            <div class="empty-icon">🏢</div>
            <p>No business inquiries found.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?tab=<?= $tab ?>&page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>">‹ Prev</a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <?php if ($p === $page): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?tab=<?= $tab ?>&page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?tab=<?= $tab ?>&page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date=<?= urlencode($date_filter) ?>">Next ›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<!-- DETAIL MODAL -->
<div class="modal-bg" id="detailModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal()">×</button>
        <h3 id="modalTitle"></h3>
        <hr class="divider">
        <div id="modalBody"></div>
    </div>
</div>

<script>
function showDetail(title, rows) {
    document.getElementById('modalTitle').textContent = title;
    let html = '';
    rows.forEach(function(r) {
        html += `<div class="detail-row">
                    <div class="detail-label">${r[0]}</div>
                    <div class="detail-value">${r[1]}</div>
                 </div>`;
    });
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('detailModal').classList.add('open');
}
function closeModal() {
    document.getElementById('detailModal').classList.remove('open');
}
document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
</script>

</body>
</html>
