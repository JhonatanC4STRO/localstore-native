<?php
/**
 * Verificaciones de Identidad — ComercioLocal Admin
 * Rediseño con topbar único, tarjetas por solicitud y panel lateral con dona.
 */
require_once('../../config/conexion.php');
session_start();

if (!isset($_SESSION['user'])) { header("Location: ../auth/login.php"); exit(); }
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin' && $role !== 'super_admin') { header("Location: ../home.php"); exit(); }

$user         = $_SESSION['user'];
$userInitial  = strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1));
$userName     = $user['full_name'] ?? 'Administrador';
$isSuperAdmin = ($role === 'super_admin');

/* ── Asegurar tabla ── */
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS seller_verifications (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        user_id           INT NOT NULL,
        verification_type VARCHAR(20)  NOT NULL DEFAULT 'negocio',
        store_name        VARCHAR(255) NOT NULL,
        category          VARCHAR(100) DEFAULT NULL,
        city              VARCHAR(100) DEFAULT NULL,
        phone             VARCHAR(30)  DEFAULT NULL,
        document_type     VARCHAR(100) DEFAULT NULL,
        document_path     VARCHAR(255) DEFAULT NULL,
        description       TEXT         DEFAULT NULL,
        status            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        rejection_reason  TEXT         DEFAULT NULL,
        reviewed_by       INT          DEFAULT NULL,
        reviewed_at       TIMESTAMP    DEFAULT NULL,
        created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user   (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$res = mysqli_query($conn, "
    SELECT
        sv.id, sv.user_id, sv.verification_type, sv.store_name, sv.category, sv.city, sv.phone,
        sv.document_type, sv.document_path, sv.description,
        sv.status, sv.rejection_reason,
        sv.reviewed_by, sv.reviewed_at, sv.created_at,
        u.full_name     AS user_name,
        u.email         AS user_email,
        u.profile_photo AS user_photo,
        ra.full_name    AS reviewer_name
    FROM seller_verifications sv
    LEFT JOIN users u  ON u.id  = sv.user_id
    LEFT JOIN users ra ON ra.id = sv.reviewed_by
    ORDER BY FIELD(sv.status,'pending','rejected','approved'), sv.created_at DESC
");

$imgExts  = ['jpg','jpeg','png','webp','gif'];
$requests = [];
$cnt = ['total'=>0,'pending'=>0,'approved'=>0,'rejected'=>0];

/* Tiempo promedio de revisión (en horas) — sobre solicitudes ya revisadas */
$avgRes = @mysqli_query($conn, "
    SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) AS h
    FROM seller_verifications
    WHERE reviewed_at IS NOT NULL AND status IN ('approved','rejected')
");
$avg_hours = 0;
if ($avgRes && ($r = mysqli_fetch_assoc($avgRes))) $avg_hours = (float) ($r['h'] ?? 0);

/* Helper para enmascarar número de documento si viene dentro del document_type */
function mask_doc_number(string $raw): string {
    if (preg_match('/\d{4,}/', $raw, $m)) {
        $num = $m[0];
        $tail = substr($num, -3);
        return str_replace($num, str_repeat('•', max(3, strlen($num)-3)) . $tail, $raw);
    }
    return $raw;
}

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $cnt['total']++;
        $cnt[$row['status']] = ($cnt[$row['status']] ?? 0) + 1;

        $docPath = $row['document_path'] ?? '';
        $ext     = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));
        $isImage = in_array($ext, $imgExts);
        $docUrl  = $docPath ? '../../../' . ltrim($docPath, '/') : '';

        $selfieUrl = '';
        if (!empty($row['user_photo'])) {
            $selfieUrl = '../../../public/uploads/perfiles/' . $row['user_photo'];
        }

        $initials = strtoupper(mb_substr($row['user_name'] ?? $row['store_name'] ?? 'V', 0, 1));
        $createdTs = strtotime($row['created_at']);
        $isUrgent  = ($row['status'] === 'pending' && $createdTs < (time() - 48*3600));

        $requests[] = [
            'id'            => (int) $row['id'],
            'user_id'       => (int) $row['user_id'],
            'v_type'        => $row['verification_type'] ?? 'negocio',
            'store_name'    => $row['store_name'],
            'user_name'     => $row['user_name'] ?? 'Vendedor',
            'user_email'    => $row['user_email'] ?? '',
            'city'          => $row['city'] ?? '—',
            'category'      => $row['category'] ?? '',
            'phone'         => $row['phone'] ?? '',
            'doc_type'      => $row['document_type'] ?? 'Documento',
            'doc_masked'    => mask_doc_number($row['document_type'] ?? ''),
            'doc_url'       => $docUrl,
            'doc_is_image'  => $isImage,
            'selfie_url'    => $selfieUrl,
            'initials'      => $initials,
            'status'        => $row['status'],
            'rejection'     => $row['rejection_reason'] ?? '',
            'created_at'    => $row['created_at'],
            'reviewed_at'   => $row['reviewed_at'] ?? '',
            'reviewer_name' => $row['reviewer_name'] ?? '',
            'urgent'        => $isUrgent,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificaciones — ComercioLocal Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

  <style>
    :root{
      --primary:#0B2E17; --primary-2:#123D20; --accent:#F5A81C; --accent-2:#FFC154;
      --red:#E53935; --red-soft:#FEECEB; --red-border:#FCA5A5;
      --green:#16A34A; --green-soft:#E6F3EB; --yellow-soft:#FFF3D6; --blue-soft:#EEF5FF;
      --bg:#F8F9FA; --white:#FFF; --ink:#0F172A; --ink2:#334155; --ink3:#64748B; --ink4:#94A3B8;
      --bord:#E5E7EB; --bord-soft:#EEF0F3; --shadow:0 2px 12px rgba(0,0,0,.06); --shadow-lg:0 8px 28px rgba(0,0,0,.08);
    }
    *{box-sizing:border-box;} html,body{margin:0;padding:0;}
    body{background:var(--bg);color:var(--ink);font-family:'DM Sans',system-ui,sans-serif;font-size:14px;line-height:1.45;}
    h1,h2,h3,h4{font-family:'Syne','DM Sans',sans-serif;color:var(--primary);letter-spacing:-.01em;margin:0;}
    a{color:inherit;text-decoration:none;} button{font-family:inherit;}

    /* TOPBAR */
    .topbar{background:var(--white);border-bottom:1px solid var(--bord);padding:14px 32px;display:flex;align-items:center;gap:32px;position:sticky;top:0;z-index:50;}
    .tb-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.15rem;color:var(--primary);display:flex;align-items:center;gap:8px;}
    .tb-logo-mark{width:32px;height:32px;border-radius:9px;background:var(--primary);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:.85rem;}
    .tb-nav{display:flex;gap:4px;margin:0 auto;}
    .tb-link{padding:8px 14px;border-radius:10px;font-weight:500;font-size:.88rem;color:var(--ink2);transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .tb-link:hover{background:var(--bg);color:var(--primary);}
    .tb-link.active{background:var(--primary);color:var(--white);}
    .tb-right{display:flex;align-items:center;gap:14px;}
    .tb-bell{position:relative;width:40px;height:40px;border-radius:10px;border:1px solid var(--bord);background:var(--white);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;}
    .tb-bell:hover{border-color:var(--primary);color:var(--primary);}
    .tb-bell__badge{position:absolute;top:-4px;right:-4px;background:var(--accent);color:var(--primary);font-size:.65rem;font-weight:800;min-width:18px;height:18px;padding:0 5px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:2px solid var(--white);}
    .tb-user-wrap{position:relative;}
    .tb-user{display:flex;align-items:center;gap:10px;padding:4px 10px 4px 4px;border-radius:10px;border:1px solid var(--bord);background:var(--white);cursor:pointer;font-family:inherit;transition:.15s;}
    .tb-user:hover{border-color:var(--primary);}
    .tb-user__av{width:34px;height:34px;border-radius:8px;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.88rem;}
    .tb-user__info{text-align:left;}
    .tb-user__info strong{font-size:.85rem;color:var(--ink);display:block;line-height:1.1;}
    .tb-user__info span{font-size:.7rem;color:var(--ink4);}
    .tb-user__caret{font-size:.7rem;color:var(--ink4);transition:.2s;}
    .tb-user-wrap.open .tb-user__caret{transform:rotate(180deg);color:var(--primary);}
    .tb-menu{position:absolute;right:0;top:calc(100% + 8px);background:var(--white);border:1px solid var(--bord);border-radius:12px;box-shadow:var(--shadow-lg);min-width:220px;padding:6px;z-index:60;opacity:0;pointer-events:none;transform:translateY(-4px);transition:.15s;}
    .tb-user-wrap.open .tb-menu{opacity:1;pointer-events:auto;transform:translateY(0);}
    .tb-menu__head{padding:10px 12px 6px;border-bottom:1px solid var(--bord-soft);margin-bottom:4px;}
    .tb-menu__head strong{display:block;font-size:.86rem;color:var(--ink);line-height:1.2;}
    .tb-menu__head small{font-size:.72rem;color:var(--ink4);}
    .tb-menu__item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:.86rem;color:var(--ink2);cursor:pointer;transition:.12s;}
    .tb-menu__item i{color:var(--ink3);width:16px;font-size:.95rem;}
    .tb-menu__item:hover{background:var(--bg);color:var(--primary);}
    .tb-menu__item:hover i{color:var(--primary);}
    .tb-menu__item.danger{color:var(--red);}
    .tb-menu__item.danger i{color:var(--red);}
    .tb-menu__item.danger:hover{background:var(--red-soft);}
    .tb-menu__sep{height:1px;background:var(--bord-soft);margin:4px 0;}

    /* PAGE */
    .page{max-width:1320px;margin:0 auto;padding:28px 32px 48px;}
    .page-head{display:flex;align-items:center;gap:18px;margin-bottom:6px;flex-wrap:wrap;}
    .page-head h1{font-size:1.8rem;font-weight:700;}
    .pending-badge{display:inline-flex;align-items:center;gap:8px;background:var(--accent);color:var(--primary);padding:8px 16px;border-radius:999px;font-weight:700;font-size:.95rem;box-shadow:0 2px 8px rgba(245,168,28,.3);}
    .pending-badge strong{font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:800;}
    .page-sub{color:var(--ink3);font-size:.9rem;margin-bottom:22px;margin-top:6px;}

    /* TABS */
    .tabs{display:flex;gap:4px;background:var(--white);padding:6px;border-radius:12px;border:1px solid var(--bord);margin-bottom:22px;width:max-content;max-width:100%;overflow-x:auto;}
    .tab-btn{padding:9px 16px;border:0;background:transparent;border-radius:9px;font-size:.86rem;font-weight:600;color:var(--ink3);cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;}
    .tab-btn:hover{color:var(--primary);}
    .tab-btn.active{background:var(--primary);color:var(--white);}
    .tab-badge{background:var(--accent);color:var(--primary);padding:2px 8px;border-radius:999px;font-size:.7rem;font-weight:800;}
    .tab-btn.active .tab-badge{background:var(--accent);color:var(--primary);}
    .tab-btn:not(.active) .tab-badge.neutral{background:#F1F5F9;color:var(--ink3);}
    .tab-btn.active .tab-badge.neutral{background:rgba(255,255,255,.2);color:var(--white);}

    /* LAYOUT */
    .layout{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:20px;align-items:start;}

    /* REQUEST CARD */
    .req-list{display:flex;flex-direction:column;gap:16px;}
    .req{background:var(--white);border-radius:16px;box-shadow:var(--shadow);padding:20px;position:relative;overflow:hidden;transition:.2s;}
    .req:hover{transform:translateY(-1px);box-shadow:var(--shadow-lg);}
    .req--urgent::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--accent);}

    .req-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:16px;flex-wrap:wrap;}
    .req-av{width:52px;height:52px;border-radius:14px;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:1.1rem;overflow:hidden;flex-shrink:0;}
    .req-av img{width:100%;height:100%;object-fit:cover;}
    .req-who{flex:1;min-width:0;}
    .req-who strong{font-size:1.05rem;color:var(--ink);display:block;line-height:1.2;}
    .req-who .meta{font-size:.8rem;color:var(--ink3);margin-top:3px;display:flex;gap:10px;flex-wrap:wrap;}
    .req-who .meta i{font-size:.85rem;margin-right:3px;}
    .req-head-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}

    .status-chip{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:999px;font-size:.72rem;font-weight:700;}
    .chip-pending{background:var(--yellow-soft);color:#B45309;}
    .chip-approved{background:var(--green-soft);color:var(--primary);}
    .chip-rejected{background:var(--red-soft);color:var(--red);}
    .urgent-chip{background:var(--accent);color:var(--primary);font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-flex;align-items:center;gap:4px;}

    .req-docs{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;}
    .doc-slot{border:1px solid var(--bord);border-radius:12px;overflow:hidden;background:var(--bg);}
    .doc-thumb{aspect-ratio:3/2;background:var(--bord-soft);position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;}
    .doc-thumb img{width:100%;height:100%;object-fit:cover;}
    .doc-thumb .ph{color:var(--ink4);font-size:2rem;}
    .doc-info{padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--white);}
    .doc-info .txt{font-size:.78rem;color:var(--ink2);}
    .doc-info .txt strong{display:block;color:var(--ink);font-size:.85rem;}
    .btn-ghost{border:1px solid var(--bord);background:var(--white);color:var(--primary);padding:6px 11px;border-radius:8px;font-size:.75rem;font-weight:600;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;}
    .btn-ghost:hover{border-color:var(--primary);background:var(--green-soft);}

    .req-footer{display:flex;align-items:center;gap:10px;padding-top:14px;border-top:1px solid var(--bord-soft);flex-wrap:wrap;}
    .req-date{font-size:.8rem;color:var(--ink3);margin-right:auto;display:inline-flex;align-items:center;gap:6px;}
    .btn-approve{background:var(--primary);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.88rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-approve:hover{background:var(--primary-2);}
    .btn-reject{background:var(--white);color:var(--red);border:1px solid var(--red-border);padding:10px 18px;border-radius:10px;font-weight:600;font-size:.88rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-reject:hover{background:var(--red-soft);border-color:var(--red);}
    .btn-approve:disabled,.btn-reject:disabled{opacity:.5;cursor:not-allowed;}

    .reviewed-by{font-size:.8rem;color:var(--ink3);display:inline-flex;align-items:center;gap:6px;}
    .reviewed-by strong{color:var(--ink);font-weight:600;}

    .rejection-note{margin-top:10px;background:var(--red-soft);color:var(--red);padding:10px 12px;border-radius:10px;font-size:.8rem;display:flex;gap:8px;align-items:flex-start;}
    .rejection-note i{margin-top:2px;}

    /* EMPTY */
    .empty{background:var(--white);border-radius:16px;box-shadow:var(--shadow);padding:64px 24px;text-align:center;color:var(--ink4);}
    .empty i{font-size:3rem;color:#CBD5E1;display:block;margin-bottom:12px;}
    .empty strong{display:block;color:var(--ink2);font-size:1.05rem;margin-bottom:4px;}

    /* SIDEBAR */
    .sbar{display:flex;flex-direction:column;gap:18px;position:sticky;top:90px;}
    .sbar-title{font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);padding:0 4px;}
    .card{background:var(--white);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}
    .card-pad{padding:20px;}
    .card-head{padding:18px 20px 6px;}
    .card-head h3{font-size:1rem;font-weight:700;}
    .card-sub{font-size:.78rem;color:var(--ink3);margin-top:2px;}

    .donut-wrap{display:flex;align-items:center;justify-content:center;padding:10px 0 6px;position:relative;}
    .donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;}
    .donut-center strong{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--primary);line-height:1;}
    .donut-center span{font-size:.7rem;color:var(--ink3);}
    .donut-legend{padding:6px 20px 18px;display:flex;flex-direction:column;gap:10px;}
    .leg{display:flex;align-items:center;gap:10px;font-size:.84rem;}
    .leg .dot{width:10px;height:10px;border-radius:3px;}
    .leg .lbl{color:var(--ink2);flex:1;}
    .leg .val{font-weight:700;color:var(--ink);}

    .avg-card{padding:18px 20px;display:flex;align-items:center;gap:14px;}
    .avg-ico{width:46px;height:46px;border-radius:12px;background:var(--accent);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
    .avg-text strong{display:block;font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);line-height:1;}
    .avg-text span{font-size:.78rem;color:var(--ink3);margin-top:2px;display:block;}

    /* LIGHTBOX */
    .lb{position:fixed;inset:0;background:rgba(11,46,23,.88);display:none;align-items:center;justify-content:center;z-index:200;padding:20px;}
    .lb.open{display:flex;}
    .lb-content{max-width:min(95vw,1100px);max-height:90vh;position:relative;}
    .lb-content img{max-width:100%;max-height:90vh;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.5);}
    .lb-close{position:absolute;top:-14px;right:-14px;width:40px;height:40px;border-radius:50%;background:var(--white);color:var(--primary);border:0;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-lg);}
    .lb-meta{position:absolute;bottom:-36px;left:0;color:rgba(255,255,255,.85);font-size:.82rem;}

    /* MODAL */
    .modal-bg{position:fixed;inset:0;background:rgba(11,46,23,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:120;padding:16px;}
    .modal-bg.open{display:flex;}
    .modal{background:var(--white);border-radius:18px;width:100%;max-width:500px;box-shadow:var(--shadow-lg);overflow:hidden;animation:mIn .22s ease-out;}
    @keyframes mIn{from{opacity:0;transform:translateY(12px) scale(.97);}to{opacity:1;transform:none;}}
    .m-head{padding:22px 24px 4px;display:flex;gap:12px;align-items:flex-start;}
    .m-head-ico{width:44px;height:44px;border-radius:12px;background:var(--red-soft);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
    .m-head h2{font-size:1.15rem;font-weight:700;margin-bottom:2px;}
    .m-head p{margin:0;font-size:.82rem;color:var(--ink3);}
    .m-body{padding:14px 24px 6px;display:flex;flex-direction:column;gap:12px;}
    .m-body label{font-size:.78rem;font-weight:600;color:var(--ink2);display:block;margin-bottom:6px;}
    .m-body textarea{width:100%;min-height:110px;padding:12px;border:1px solid var(--bord);border-radius:10px;font-family:inherit;font-size:.88rem;outline:none;resize:vertical;background:var(--bg);transition:.15s;}
    .m-body textarea:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .m-info{background:var(--blue-soft);color:#1E40AF;padding:10px 12px;border-radius:10px;font-size:.78rem;display:flex;gap:8px;align-items:flex-start;}
    .m-info i{margin-top:1px;}
    .m-err{background:var(--red-soft);color:var(--red);padding:10px 12px;border-radius:10px;font-size:.78rem;display:none;}
    .m-err.show{display:block;}
    .m-actions{padding:16px 24px 22px;display:flex;gap:10px;justify-content:flex-end;}
    .btn-sec{background:var(--bg);color:var(--ink2);border:1px solid var(--bord);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;}
    .btn-sec:hover{background:var(--bord-soft);}
    .btn-danger{background:var(--red);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;}
    .btn-danger:hover{background:#C62828;}
    .btn-danger:disabled{opacity:.55;cursor:not-allowed;}

    /* TOAST */
    .toast-area{position:fixed;bottom:24px;right:24px;display:flex;flex-direction:column;gap:10px;z-index:220;}
    .toast{background:var(--primary);color:var(--white);padding:13px 18px;border-radius:12px;box-shadow:var(--shadow-lg);font-size:.86rem;font-weight:600;display:flex;align-items:center;gap:10px;min-width:260px;animation:tIn .25s;}
    .toast.err{background:var(--red);} .toast.warn{background:var(--accent);color:var(--primary);}
    @keyframes tIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:none;}}
    .toast button{background:transparent;border:0;color:inherit;opacity:.7;cursor:pointer;margin-left:auto;}

    @media (max-width:1100px){
      .layout{grid-template-columns:1fr;}
      .sbar{position:static;flex-direction:row;overflow-x:auto;}
      .sbar > *{min-width:280px;}
    }
    @media (max-width:720px){
      .topbar{padding:12px 16px;flex-wrap:wrap;gap:12px;}
      .tb-nav{order:3;width:100%;overflow-x:auto;margin:0;}
      .page{padding:20px 16px 40px;}
      .req-docs{grid-template-columns:1fr;}
      .tb-user__info{display:none;}
    }
  </style>
</head>
<body>

  <!-- TOPBAR -->
  <header class="topbar">
    <a href="./dashboard.php" class="tb-logo">
      <span class="tb-logo-mark"><i class="bi bi-shop-window"></i></span>
      ComercioLocal
    </a>
    <nav class="tb-nav">
      <a class="tb-link" href="./dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <a class="tb-link" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
      <a class="tb-link active" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
      <a class="tb-link" href="./reportes.php"><i class="bi bi-flag-fill"></i> Reportes</a>
    </nav>
    <div class="tb-right">
      <button class="tb-bell" title="Notificaciones">
        <i class="bi bi-bell-fill"></i>
        <?php if ($cnt['pending'] > 0): ?>
          <span class="tb-bell__badge"><?= $cnt['pending'] > 9 ? '9+' : $cnt['pending'] ?></span>
        <?php endif; ?>
      </button>
      <div class="tb-user-wrap">
        <button class="tb-user" type="button" onclick="toggleTbMenu(event)">
          <div class="tb-user__av"><?= htmlspecialchars($userInitial) ?></div>
          <div class="tb-user__info">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <span><?= $isSuperAdmin ? 'Super Administrador' : 'Administrador' ?></span>
          </div>
          <i class="bi bi-chevron-down tb-user__caret"></i>
        </button>
        <div class="tb-menu">
          <div class="tb-menu__head">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <small><?= htmlspecialchars($user['email'] ?? '') ?></small>
          </div>
          <a class="tb-menu__item" href="../home.php"><i class="bi bi-house-door"></i> Ir al sitio</a>
          <a class="tb-menu__item" href="../perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
          <div class="tb-menu__sep"></div>
          <a class="tb-menu__item danger" href="../../controllers/auth_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
  </header>

  <script>
    function toggleTbMenu(e){e.stopPropagation();e.currentTarget.closest('.tb-user-wrap').classList.toggle('open');}
    document.addEventListener('click',e=>{if(!e.target.closest('.tb-user-wrap'))document.querySelectorAll('.tb-user-wrap.open').forEach(el=>el.classList.remove('open'));});
  </script>

  <div class="page">

    <!-- Header -->
    <div class="page-head">
      <h1>Verificaciones de identidad</h1>
      <?php if ($cnt['pending'] > 0): ?>
        <span class="pending-badge">
          <i class="bi bi-hourglass-split"></i>
          <strong><?= $cnt['pending'] ?></strong> pendientes
        </span>
      <?php endif; ?>
    </div>
    <p class="page-sub">Revisa, aprueba o rechaza las solicitudes de verificación de los vendedores.</p>

    <!-- Tabs -->
    <div class="tabs" role="tablist">
      <button class="tab-btn active" data-filter="pending" onclick="setFilter('pending',this)">
        Pendientes
        <?php if ($cnt['pending'] > 0): ?><span class="tab-badge"><?= $cnt['pending'] ?></span><?php endif; ?>
      </button>
      <button class="tab-btn" data-filter="approved" onclick="setFilter('approved',this)">
        Aprobadas <span class="tab-badge neutral"><?= $cnt['approved'] ?></span>
      </button>
      <button class="tab-btn" data-filter="rejected" onclick="setFilter('rejected',this)">
        Rechazadas <span class="tab-badge neutral"><?= $cnt['rejected'] ?></span>
      </button>
      <button class="tab-btn" data-filter="all" onclick="setFilter('all',this)">
        Todas <span class="tab-badge neutral"><?= $cnt['total'] ?></span>
      </button>
    </div>

    <!-- Layout -->
    <div class="layout">

      <!-- Main: lista de solicitudes -->
      <section>
        <div class="req-list" id="reqList">
          <?php if (empty($requests)): ?>
            <div class="empty"><i class="bi bi-patch-check"></i><strong>No hay solicitudes registradas</strong><div style="font-size:.85rem;">Cuando los vendedores envíen sus documentos aparecerán aquí.</div></div>
          <?php else: foreach ($requests as $req):
              $id      = $req['id'];
              $status  = $req['status'];
              $created = date('d M Y · H:i', strtotime($req['created_at']));
              $reviewed = $req['reviewed_at'] ? date('d M Y · H:i', strtotime($req['reviewed_at'])) : '';
          ?>
            <article class="req <?= $req['urgent'] ? 'req--urgent' : '' ?>" data-status="<?= $status ?>" data-id="<?= $id ?>">
              <div class="req-head">
                <div class="req-av">
                  <?php if (!empty($req['selfie_url'])): ?>
                    <img src="<?= htmlspecialchars($req['selfie_url']) ?>" alt="" onerror="this.parentNode.textContent='<?= htmlspecialchars($req['initials']) ?>'">
                  <?php else: ?>
                    <?= htmlspecialchars($req['initials']) ?>
                  <?php endif; ?>
                </div>
                <div class="req-who">
                  <strong><?= htmlspecialchars($req['user_name']) ?></strong>
                  <div class="meta">
                    <span><i class="bi bi-geo-alt"></i><?= htmlspecialchars($req['city']) ?></span>
                    <?php if ($req['store_name']): ?><span><i class="bi bi-shop"></i><?= htmlspecialchars($req['store_name']) ?></span><?php endif; ?>
                    <?php if ($req['user_email']): ?><span><i class="bi bi-envelope"></i><?= htmlspecialchars($req['user_email']) ?></span><?php endif; ?>
                  </div>
                </div>
                <div class="req-head-right">
                  <?php if ($status === 'pending'): ?>
                    <span class="status-chip chip-pending"><i class="bi bi-hourglass-split"></i>Pendiente</span>
                    <?php if ($req['urgent']): ?><span class="urgent-chip"><i class="bi bi-exclamation-triangle-fill"></i>Urgente · +48h</span><?php endif; ?>
                  <?php elseif ($status === 'approved'): ?>
                    <span class="status-chip chip-approved"><i class="bi bi-patch-check-fill"></i>Aprobada</span>
                  <?php else: ?>
                    <span class="status-chip chip-rejected"><i class="bi bi-x-circle-fill"></i>Rechazada</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="req-docs">
                <!-- Documento -->
                <div class="doc-slot">
                  <div class="doc-thumb">
                    <?php if ($req['doc_url'] && $req['doc_is_image']): ?>
                      <img src="<?= htmlspecialchars($req['doc_url']) ?>" alt="Documento">
                    <?php else: ?>
                      <i class="bi bi-file-earmark-text ph"></i>
                    <?php endif; ?>
                  </div>
                  <div class="doc-info">
                    <div class="txt">
                      <strong><?= htmlspecialchars($req['doc_type'] ?: 'Documento') ?></strong>
                      <?= htmlspecialchars($req['doc_masked'] ?: 'Identificación') ?>
                    </div>
                    <?php if ($req['doc_url']): ?>
                      <?php if ($req['doc_is_image']): ?>
                        <button class="btn-ghost" onclick="openLB('<?= htmlspecialchars($req['doc_url']) ?>','Documento de <?= htmlspecialchars($req['user_name']) ?>')"><i class="bi bi-zoom-in"></i>Ver completo</button>
                      <?php else: ?>
                        <a class="btn-ghost" target="_blank" href="<?= htmlspecialchars($req['doc_url']) ?>"><i class="bi bi-box-arrow-up-right"></i>Abrir PDF</a>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Selfie / Foto de perfil -->
                <div class="doc-slot">
                  <div class="doc-thumb">
                    <?php if (!empty($req['selfie_url'])): ?>
                      <img src="<?= htmlspecialchars($req['selfie_url']) ?>" alt="Selfie">
                    <?php else: ?>
                      <i class="bi bi-person-circle ph"></i>
                    <?php endif; ?>
                  </div>
                  <div class="doc-info">
                    <div class="txt">
                      <strong>Selfie de verificación</strong>
                      <?= !empty($req['selfie_url']) ? 'Foto de perfil registrada' : 'Sin selfie' ?>
                    </div>
                    <?php if (!empty($req['selfie_url'])): ?>
                      <button class="btn-ghost" onclick="openLB('<?= htmlspecialchars($req['selfie_url']) ?>','Selfie de <?= htmlspecialchars($req['user_name']) ?>')"><i class="bi bi-zoom-in"></i>Ver selfie</button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php if ($status === 'rejected' && !empty($req['rejection'])): ?>
                <div class="rejection-note">
                  <i class="bi bi-info-circle-fill"></i>
                  <div><strong>Motivo del rechazo:</strong> <?= htmlspecialchars($req['rejection']) ?></div>
                </div>
              <?php endif; ?>

              <div class="req-footer">
                <span class="req-date"><i class="bi bi-clock"></i>Solicitud: <?= $created ?></span>

                <?php if ($status === 'pending'): ?>
                  <button class="btn-reject" data-id="<?= $id ?>" onclick="openRejectModal(<?= $id ?>,'<?= htmlspecialchars(addslashes($req['user_name']), ENT_QUOTES) ?>')">
                    <i class="bi bi-x-lg"></i> Rechazar
                  </button>
                  <button class="btn-approve" data-id="<?= $id ?>" onclick="approve(<?= $id ?>, this)">
                    <i class="bi bi-check-lg"></i> Aprobar
                  </button>
                <?php else: ?>
                  <span class="reviewed-by">
                    <i class="bi bi-person-check"></i>
                    <?= $status === 'approved' ? 'Aprobada' : 'Rechazada' ?>
                    <?php if ($req['reviewer_name']): ?>por <strong><?= htmlspecialchars($req['reviewer_name']) ?></strong><?php endif; ?>
                    <?php if ($reviewed): ?>· <?= $reviewed ?><?php endif; ?>
                  </span>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; endif; ?>
        </div>

        <div class="empty" id="emptyFilter" style="display:none;margin-top:16px;">
          <i class="bi bi-inbox"></i><strong>Sin resultados en este filtro</strong>
          <div style="font-size:.85rem;">Cambia de pestaña para ver otras solicitudes.</div>
        </div>
      </section>

      <!-- Sidebar -->
      <aside class="sbar">
        <div class="sbar-title">Resumen</div>

        <div class="card">
          <div class="card-head">
            <h3>Estado de verificaciones</h3>
            <div class="card-sub">Distribución histórica.</div>
          </div>
          <div class="donut-wrap">
            <canvas id="donut" width="180" height="180"></canvas>
            <div class="donut-center">
              <strong><?= $cnt['total'] ?></strong>
              <span>Total</span>
            </div>
          </div>
          <div class="donut-legend">
            <div class="leg"><span class="dot" style="background:#0B2E17;"></span><span class="lbl">Aprobadas</span><span class="val"><?= $cnt['approved'] ?></span></div>
            <div class="leg"><span class="dot" style="background:#F5A81C;"></span><span class="lbl">Pendientes</span><span class="val"><?= $cnt['pending'] ?></span></div>
            <div class="leg"><span class="dot" style="background:#E53935;"></span><span class="lbl">Rechazadas</span><span class="val"><?= $cnt['rejected'] ?></span></div>
          </div>
        </div>

        <div class="card avg-card">
          <div class="avg-ico"><i class="bi bi-stopwatch-fill"></i></div>
          <div class="avg-text">
            <strong><?= $avg_hours > 0 ? number_format($avg_hours, 1, ',', '.') . 'h' : '—' ?></strong>
            <span>Tiempo promedio de revisión</span>
          </div>
        </div>
      </aside>

    </div>
  </div>

  <!-- LIGHTBOX -->
  <div class="lb" id="lightbox" onclick="if(event.target===this) closeLB()">
    <div class="lb-content">
      <button class="lb-close" onclick="closeLB()"><i class="bi bi-x-lg"></i></button>
      <img id="lbImg" src="" alt="">
      <div class="lb-meta" id="lbMeta"></div>
    </div>
  </div>

  <!-- MODAL RECHAZAR -->
  <div class="modal-bg" id="mReject">
    <div class="modal">
      <div class="m-head">
        <div class="m-head-ico"><i class="bi bi-x-circle-fill"></i></div>
        <div>
          <h2>Rechazar verificación</h2>
          <p id="rejSubtitle">Vendedor: —</p>
        </div>
      </div>
      <div class="m-body">
        <div class="m-err" id="rejErr"></div>
        <div>
          <label>Motivo del rechazo <span style="color:var(--red);">*</span></label>
          <textarea id="rejReason" placeholder="Ej: El documento no es legible, por favor sube una foto más clara."></textarea>
        </div>
        <div class="m-info">
          <i class="bi bi-info-circle-fill"></i>
          <span>El vendedor recibirá un correo con este motivo y podrá reenviar su documentación.</span>
        </div>
      </div>
      <div class="m-actions">
        <button class="btn-sec" onclick="closeRejectModal()">Cancelar</button>
        <button class="btn-danger" id="rejSubmit" onclick="submitReject()"><i class="bi bi-send"></i> Enviar rechazo</button>
      </div>
    </div>
  </div>

  <div class="toast-area" id="toastArea"></div>

  <script>
    const DONUT_DATA = [<?= (int)$cnt['approved'] ?>, <?= (int)$cnt['pending'] ?>, <?= (int)$cnt['rejected'] ?>];
    const API = '../../api/verificacion.php';
    let currentFilter = 'pending';
    let rejectCtx = null;

    window.addEventListener('load', () => {
      const total = DONUT_DATA.reduce((a,b)=>a+b,0);
      const c = document.getElementById('donut');
      if (c && total > 0 && window.Chart) {
        new Chart(c, {
          type:'doughnut',
          data:{ labels:['Aprobadas','Pendientes','Rechazadas'], datasets:[{ data:DONUT_DATA, backgroundColor:['#0B2E17','#F5A81C','#E53935'], borderWidth:3, borderColor:'#fff', hoverOffset:6 }] },
          options:{ cutout:'70%', responsive:false, plugins:{ legend:{ display:false } } },
        });
      }
      applyFilter();
    });

    /* Tabs */
    function setFilter(f, btn) {
      currentFilter = f;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilter();
    }
    function applyFilter() {
      let shown = 0;
      document.querySelectorAll('.req').forEach(c => {
        const match = currentFilter === 'all' || c.dataset.status === currentFilter;
        c.style.display = match ? '' : 'none';
        if (match) shown++;
      });
      const total = document.querySelectorAll('.req').length;
      document.getElementById('emptyFilter').style.display = (total > 0 && shown === 0) ? 'block' : 'none';
    }

    /* Lightbox */
    function openLB(src, meta) {
      document.getElementById('lbImg').src = src;
      document.getElementById('lbMeta').textContent = meta || '';
      document.getElementById('lightbox').classList.add('open');
    }
    function closeLB() { document.getElementById('lightbox').classList.remove('open'); }

    /* Modal rechazo */
    function openRejectModal(id, name) {
      rejectCtx = { id, name };
      document.getElementById('rejSubtitle').textContent = `Vendedor: ${name}`;
      document.getElementById('rejReason').value = '';
      document.getElementById('rejErr').classList.remove('show');
      document.getElementById('mReject').classList.add('open');
      setTimeout(() => document.getElementById('rejReason').focus(), 150);
    }
    function closeRejectModal() {
      document.getElementById('mReject').classList.remove('open');
      rejectCtx = null;
    }

    async function submitReject() {
      if (!rejectCtx) return;
      const reason = document.getElementById('rejReason').value.trim();
      if (reason.length < 5) { showErr('rejErr', 'El motivo debe tener al menos 5 caracteres.'); return; }

      const btn = document.getElementById('rejSubmit');
      btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando…';
      try {
        const r = await fetch(API, {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ action:'reject', id: rejectCtx.id, reason }),
        });
        const j = await r.json();
        if (j.ok) {
          toast('success', `"${rejectCtx.name}" ha sido notificado.`);
          closeRejectModal();
          setTimeout(() => location.reload(), 700);
        } else { showErr('rejErr', j.error || 'No se pudo procesar.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Enviar rechazo'; }
      } catch (e) { showErr('rejErr', 'Error de conexión.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Enviar rechazo'; }
    }

    /* Aprobar */
    async function approve(id, btn) {
      const card = btn.closest('.req');
      const row = card.querySelectorAll('button');
      row.forEach(b => b.disabled = true);
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Aprobando…';
      try {
        const r = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ action:'approve', id }) });
        const j = await r.json();
        if (j.ok) {
          toast('success', 'Solicitud aprobada correctamente.');
          setTimeout(() => location.reload(), 700);
        } else {
          toast('error', j.error || 'No se pudo aprobar.');
          row.forEach(b => b.disabled = false);
          btn.innerHTML = '<i class="bi bi-check-lg"></i> Aprobar';
        }
      } catch (e) {
        toast('error', 'Error de red.');
        row.forEach(b => b.disabled = false);
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Aprobar';
      }
    }

    /* UI helpers */
    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.add('show'); }
    function toast(type, msg) {
      const el = document.createElement('div');
      el.className = 'toast ' + (type === 'error' ? 'err' : type === 'warn' ? 'warn' : '');
      const ico = type === 'error' ? 'bi-x-circle-fill' : type === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
      el.innerHTML = `<i class="bi ${ico}"></i>${msg}<button onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
      document.getElementById('toastArea').appendChild(el);
      setTimeout(() => el.remove(), 4200);
    }

    /* Cerrar modales con Escape / click fuera */
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeRejectModal(); closeLB(); } });
    document.getElementById('mReject').addEventListener('click', e => { if (e.target.id === 'mReject') closeRejectModal(); });
  </script>
</body>
</html>
