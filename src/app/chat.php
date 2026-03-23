<?php
session_start();
include(__DIR__ . '/../config/conexion.php');

/* ── LOGIC PRESERVED ── */
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php");
    exit();
}

$user_id         = $_SESSION['user']['id'];
$conversation_id = $_GET['conversation_id'] ?? null;

$isLoggedIn  = true;
$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

/* LOGIC PRESERVED: conversations query — filtra las eliminadas por este usuario */
$sql = "SELECT
            c.id,
            p.title AS product_name,
            p.price AS product_price,
            u.full_name AS other_user,
            u.id AS other_user_id
        FROM conversations c
        JOIN products p ON c.product_id = p.id
        JOIN users u
        ON (
            (c.buyer_id  = $user_id AND u.id = c.seller_id)
            OR
            (c.seller_id = $user_id AND u.id = c.buyer_id)
        )
        WHERE (c.buyer_id = $user_id  OR c.seller_id = $user_id)
          -- No mostrar si este usuario ocultó el chat
          AND NOT (c.buyer_id  = $user_id AND c.hidden_by_buyer  = 1)
          AND NOT (c.seller_id = $user_id AND c.hidden_by_seller = 1)
        ORDER BY c.id DESC";
$conversations = mysqli_query($conn, $sql);

/* Collect for JS reuse */
$convList = [];
while ($c = mysqli_fetch_assoc($conversations)) $convList[] = $c;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mensajes – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root {
      --g900:#0b2e17; --g800:#103d1e; --g700:#185228;
      --g600:#1e6b33; --g500:#25883f; --g400:#34b357;
      --g300:#55d475; --g200:#96e8b0; --g100:#c8f2d5; --g50:#edfaf3;

      --y600:#b07000; --y500:#d48c0a; --y400:#f5a81c;
      --y300:#fcc034; --y200:#fdd878; --y100:#fef0bc;

      --ink:#0d1f13; --ink2:#2d4035; --ink3:#5a7065;
      --bg-chat:#e5ddd5;
      --bubble-me:#d1f7c4;
      --bubble-me-border:#b7e8a0;
      --bubble-other:#ffffff;
      --card:#fff; --border:#d4e8da;
      --r:14px;
      --sh:0 2px 8px rgba(10,40,20,.10);
    }

    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    html,body{height:100%;overflow:hidden;}
    body{font-family:'DM Sans',sans-serif;color:var(--ink);background:var(--g900);}
    h1,h2,h3,h4{font-family:'Syne',sans-serif;}
    a{text-decoration:none;color:inherit;}

    /* ══ APP SHELL ══ */
    .app{
      display:grid;
      grid-template-columns:360px 1fr 300px;
      height:100vh;
      background:#f0f7f2;
    }

    /* ══════════════════════════════════
       LEFT SIDEBAR – Conversations
    ══════════════════════════════════ */
    .conv-sidebar{
      display:flex;flex-direction:column;
      background:#fff;
      border-right:1.5px solid var(--border);
      height:100vh;overflow:hidden;
    }

    /* sidebar topbar */
    .cs-topbar{
      background:linear-gradient(135deg,var(--g900),var(--g800));
      padding:0 18px;height:64px;
      display:flex;align-items:center;gap:12px;
      flex-shrink:0;
      border-bottom:3px solid var(--y400);
    }
    .cs-logo{display:flex;align-items:center;gap:9px;font-family:'Syne',sans-serif;font-weight:800;font-size:1.05rem;color:#fff;}
    .cs-logo-box{width:32px;height:32px;background:var(--y400);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--g900);font-size:.9rem;}
    .cs-logo em{color:var(--y400);font-style:normal;}
    .cs-spacer{flex:1;}
    .cs-top-actions{display:flex;gap:8px;}
    .cs-icon-btn{width:34px;height:34px;border-radius:50%;border:none;background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.95rem;transition:all .2s;}
    .cs-icon-btn:hover{background:rgba(255,255,255,.2);color:#fff;}

    /* search */
    .cs-search{
      padding:10px 14px;background:var(--g50);
      border-bottom:1.5px solid var(--border);flex-shrink:0;
    }
    .cs-search-wrap{
      display:flex;align-items:center;gap:8px;
      background:#fff;border:1.5px solid var(--border);
      border-radius:50px;padding:7px 14px;
    }
    .cs-search-wrap i{color:var(--ink3);font-size:.9rem;}
    .cs-search-wrap input{flex:1;border:none;outline:none;font-family:'DM Sans',sans-serif;font-size:.84rem;color:var(--ink);}
    .cs-search-wrap input::placeholder{color:#aab8b0;}

    /* filter chips */
    .cs-filters{
      display:flex;gap:7px;padding:10px 14px 6px;
      overflow-x:auto;scrollbar-width:none;flex-shrink:0;
    }
    .cs-filters::-webkit-scrollbar{display:none;}
    .cf-chip{flex-shrink:0;padding:4px 12px;border-radius:50px;border:1.5px solid var(--border);font-size:.74rem;font-weight:600;color:var(--ink2);cursor:pointer;transition:all .2s;white-space:nowrap;}
    .cf-chip:hover{border-color:var(--g300);color:var(--g600);}
    .cf-chip.active{background:var(--g500);border-color:var(--g500);color:#fff;}

    /* conversation list */
    .conv-list{flex:1;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--g200) transparent;}
    .conv-list::-webkit-scrollbar{width:4px;}
    .conv-list::-webkit-scrollbar-thumb{background:var(--g200);border-radius:4px;}

    .conv-item{
      display:flex;align-items:center;gap:12px;
      padding:12px 16px;cursor:pointer;
      border-bottom:1px solid #f0f4f1;
      transition:background .15s;position:relative;
    }
    .conv-item:hover{background:var(--g50);}
    .conv-item.active{background:var(--g50);border-left:3px solid var(--g500);}
    .conv-item.active .ci-name{color:var(--g600);}

    /* ── DELETE BUTTON on hover ── */
    .ci-del-btn{
      position:absolute;top:8px;right:8px;
      width:24px;height:24px;border-radius:50%;
      background:#fee2e2;border:1.5px solid #fca5a5;
      color:#dc2626;
      display:none;align-items:center;justify-content:center;
      font-size:.7rem;cursor:pointer;transition:all .2s;z-index:5;
    }
    .conv-item:hover .ci-del-btn{display:flex;}
    .ci-del-btn:hover{background:#dc2626;border-color:#dc2626;color:#fff;transform:scale(1.12);}

    /* ── DELETE CONFIRM MODAL ── */
    .del-modal-overlay{
      position:fixed;inset:0;background:rgba(0,0,0,.55);
      z-index:600;display:none;align-items:center;justify-content:center;
      backdrop-filter:blur(5px);
    }
    .del-modal-overlay.show{display:flex;}
    .del-modal{
      background:#fff;border-radius:20px;padding:32px 28px;
      max-width:380px;width:90%;text-align:center;
      box-shadow:0 20px 60px rgba(0,0,0,.22);
      animation:modalIn .28s cubic-bezier(.22,.9,.36,1) both;
    }
    @keyframes modalIn{from{opacity:0;transform:scale(.92);}to{opacity:1;transform:scale(1);}}
    .del-modal-icon{
      width:58px;height:58px;border-radius:50%;
      background:#fee2e2;color:#dc2626;font-size:1.5rem;
      display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
    }
    .del-modal-title{font-family:'Syne',sans-serif;font-weight:800;font-size:1.05rem;color:#0d1f13;margin-bottom:7px;}
    .del-modal-sub{font-size:.83rem;color:#5a7065;line-height:1.55;margin-bottom:24px;}
    .del-modal-sub strong{color:#0d1f13;}
    .del-modal-btns{display:flex;gap:10px;}
    .del-modal-cancel{
      flex:1;background:#f0f7f2;border:1.5px solid #d4e8da;
      color:#2d4035;border-radius:12px;padding:12px;
      font-family:'DM Sans',sans-serif;font-weight:500;font-size:.88rem;cursor:pointer;transition:all .2s;
    }
    .del-modal-cancel:hover{border-color:#34b357;color:#1e6b33;}
    .del-modal-confirm{
      flex:1;background:#dc2626;border:none;
      color:#fff;border-radius:12px;padding:12px;
      font-family:'Syne',sans-serif;font-weight:700;font-size:.88rem;cursor:pointer;
      display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;
    }
    .del-modal-confirm:hover{background:#b91c1c;}

    /* ── TOAST NOTIFICATION ── */
    .del-toast{
      position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);
      background:#1e1e1e;color:#fff;
      border-radius:50px;padding:11px 22px;
      font-size:.84rem;display:flex;align-items:center;gap:8px;
      box-shadow:0 6px 24px rgba(0,0,0,.25);z-index:700;
      transition:transform .35s cubic-bezier(.22,.9,.36,1);pointer-events:none;
    }
    .del-toast.show{transform:translateX(-50%) translateY(0);}
    .del-toast i{color:#55d475;}

    .ci-avatar{
      width:46px;height:46px;border-radius:50%;flex-shrink:0;
      background:linear-gradient(135deg,var(--g400),var(--g500));
      display:flex;align-items:center;justify-content:center;
      font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:#fff;
      position:relative;
    }
    .ci-avatar .online-ring{position:absolute;bottom:1px;right:1px;width:12px;height:12px;border-radius:50%;background:var(--g400);border:2.5px solid #fff;}
    .ci-avatar .offline-ring{position:absolute;bottom:1px;right:1px;width:12px;height:12px;border-radius:50%;background:#ccc;border:2.5px solid #fff;}

    .ci-body{flex:1;min-width:0;}
    .ci-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;}
    .ci-name{font-size:.88rem;font-weight:700;color:var(--ink);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
    .ci-time{font-size:.68rem;color:var(--ink3);flex-shrink:0;}
    .ci-bottom{display:flex;align-items:center;gap:6px;}
    .ci-preview{font-size:.78rem;color:var(--ink3);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;flex:1;}
    .ci-product-tag{font-size:.68rem;background:var(--g100);color:var(--g700);padding:1px 6px;border-radius:10px;flex-shrink:0;max-width:80px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
    .ci-unread{min-width:19px;height:19px;border-radius:50%;background:var(--g500);color:#fff;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0 4px;}

    .cs-empty{padding:40px 20px;text-align:center;color:var(--ink3);}
    .cs-empty i{font-size:2rem;color:var(--g200);display:block;margin-bottom:10px;}
    .cs-empty p{font-size:.84rem;}

    /* ══════════════════════════════════
       CENTER – Chat Window
    ══════════════════════════════════ */
    .chat-window{display:flex;flex-direction:column;height:100vh;overflow:hidden;}

    /* chat header */
    .chat-header{
      background:linear-gradient(to right,var(--g900),var(--g800));
      border-bottom:2px solid var(--y400);
      height:64px;padding:0 18px;
      display:flex;align-items:center;gap:12px;
      flex-shrink:0;
      box-shadow:0 2px 12px rgba(0,0,0,.2);
    }
    .ch-back{width:32px;height:32px;border-radius:50%;border:none;background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.95rem;transition:all .2s;}
    .ch-back:hover{background:rgba(255,255,255,.2);color:#fff;}

    .ch-avatar{width:40px;height:40px;border-radius:50%;flex-shrink:0;background:linear-gradient(135deg,var(--y400),var(--y300));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:.95rem;color:var(--g900);position:relative;cursor:pointer;}
    .ch-av-online{position:absolute;bottom:1px;right:1px;width:10px;height:10px;border-radius:50%;background:var(--g400);border:2px solid var(--g900);}

    .ch-info{flex:1;min-width:0;}
    .ch-name{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;color:#fff;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
    .ch-status{font-size:.72rem;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:5px;margin-top:1px;}
    .ch-status-dot{width:7px;height:7px;border-radius:50%;background:var(--g400);}

    .ch-actions{display:flex;gap:6px;}
    .ch-action-btn{width:34px;height:34px;border-radius:50%;border:none;background:rgba(255,255,255,.1);color:rgba(255,255,255,.65);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;transition:all .2s;}
    .ch-action-btn:hover{background:rgba(255,255,255,.2);color:#fff;}

    /* product pin */
    .product-pin{
      background:#fff;border-bottom:1.5px solid var(--border);
      padding:10px 18px;display:flex;align-items:center;gap:12px;
      flex-shrink:0;cursor:pointer;transition:background .2s;
    }
    .product-pin:hover{background:var(--g50);}
    .pp-img{width:44px;height:44px;border-radius:10px;object-fit:cover;background:var(--g50);border:1.5px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--g200);overflow:hidden;}
    .pp-img img{width:100%;height:100%;object-fit:cover;}
    .pp-info{flex:1;min-width:0;}
    .pp-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);margin-bottom:2px;}
    .pp-title{font-size:.85rem;font-weight:600;color:var(--ink);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}
    .pp-price{font-family:'Syne',sans-serif;font-weight:800;font-size:.88rem;color:var(--g600);}
    .pp-btn{background:var(--g500);border:none;color:#fff;border-radius:10px;padding:7px 14px;font-family:'Syne',sans-serif;font-weight:700;font-size:.74rem;cursor:pointer;transition:all .2s;white-space:nowrap;flex-shrink:0;}
    .pp-btn:hover{background:var(--g600);transform:translateY(-1px);}

    /* messages area */
    .messages-area{
      flex:1;overflow-y:auto;
      padding:16px;
      background:var(--bg-chat);
      background-image:
        radial-gradient(rgba(37,136,63,.04) 1px,transparent 1px);
      background-size:22px 22px;
      display:flex;flex-direction:column;gap:2px;
      scrollbar-width:thin;scrollbar-color:rgba(0,0,0,.15) transparent;
    }
    .messages-area::-webkit-scrollbar{width:5px;}
    .messages-area::-webkit-scrollbar-thumb{background:rgba(0,0,0,.15);border-radius:5px;}

    /* date separator */
    .date-sep{text-align:center;margin:10px 0;}
    .date-sep span{background:rgba(255,255,255,.85);backdrop-filter:blur(4px);font-size:.72rem;color:var(--ink3);padding:4px 12px;border-radius:20px;box-shadow:0 1px 4px rgba(0,0,0,.1);}

    /* message bubble */
    .msg-wrap{display:flex;flex-direction:column;max-width:65%;margin-bottom:4px;}
    .msg-wrap.me{align-self:flex-end;align-items:flex-end;}
    .msg-wrap.other{align-self:flex-start;align-items:flex-start;}

    .bubble{
      padding:9px 13px;
      border-radius:16px;
      position:relative;
      box-shadow:0 1px 3px rgba(0,0,0,.12);
      max-width:100%;word-break:break-word;
    }

    .bubble.me{
      background:var(--bubble-me);
      border-bottom-right-radius:4px;
    }
    .bubble.other{
      background:var(--bubble-other);
      border-bottom-left-radius:4px;
    }

    /* bubble tail */
    .bubble.me::after{
      content:'';position:absolute;bottom:0;right:-7px;
      border:7px solid transparent;
      border-left-color:var(--bubble-me);
      border-bottom-color:var(--bubble-me);
      border-right:0;border-top:0;
    }
    .bubble.other::after{
      content:'';position:absolute;bottom:0;left:-7px;
      border:7px solid transparent;
      border-right-color:var(--bubble-other);
      border-bottom-color:var(--bubble-other);
      border-left:0;border-top:0;
    }

    .bubble-text{font-size:.88rem;line-height:1.5;color:var(--ink);}
    .bubble-meta{display:flex;align-items:center;justify-content:flex-end;gap:4px;margin-top:3px;}
    .bubble-time{font-size:.65rem;color:var(--ink3);}
    .bubble-ticks{font-size:.78rem;color:#aaa;display:flex;}
    .bubble-ticks.read{color:#34b7f1;}

    /* image message */
    .bubble-img{width:220px;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.15);cursor:pointer;}
    .bubble-img img{width:100%;display:block;transition:opacity .2s;}
    .bubble-img img:hover{opacity:.9;}

    /* system / quick reply bubble */
    .quick-replies{display:flex;flex-wrap:wrap;gap:7px;padding:6px 0 4px;justify-content:flex-end;}
    .qr-pill{background:rgba(255,255,255,.9);border:1.5px solid var(--g300);color:var(--g700);border-radius:50px;padding:5px 14px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;}
    .qr-pill:hover{background:var(--g500);border-color:var(--g500);color:#fff;}

    /* empty chat state */
    .chat-empty{
      flex:1;display:flex;flex-direction:column;
      align-items:center;justify-content:center;
      background:var(--bg-chat);
      background-image:radial-gradient(rgba(37,136,63,.04) 1px,transparent 1px);
      background-size:22px 22px;
      gap:12px;
    }
    .ce-icon{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:var(--g400);box-shadow:0 4px 16px rgba(0,0,0,.1);}
    .ce-title{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:var(--ink2);}
    .ce-sub{font-size:.84rem;color:var(--ink3);text-align:center;max-width:280px;line-height:1.5;}
    .ce-badge{display:flex;align-items:center;gap:5px;background:#fff;border:1.5px solid var(--border);border-radius:50px;padding:6px 14px;font-size:.74rem;color:var(--ink3);}
    .ce-badge i{color:var(--g400);}

    /* message input */
    .msg-input-bar{
      background:#f0f0f0;
      border-top:1px solid #ddd;
      padding:10px 14px;
      display:flex;align-items:flex-end;gap:10px;
      flex-shrink:0;
    }

    .input-side-btns{display:flex;gap:4px;}
    .input-btn{width:40px;height:40px;border-radius:50%;border:none;background:transparent;color:var(--ink3);font-size:1.15rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;}
    .input-btn:hover{background:rgba(0,0,0,.06);color:var(--g600);}

    .input-field-wrap{
      flex:1;background:#fff;border-radius:24px;
      border:1.5px solid #ddd;padding:10px 16px;
      display:flex;align-items:center;gap:8px;
      box-shadow:0 1px 4px rgba(0,0,0,.06);
    }
    .input-field-wrap textarea{
      flex:1;border:none;outline:none;resize:none;
      font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--ink);
      line-height:1.4;max-height:100px;overflow-y:auto;
      background:transparent;
    }
    .input-field-wrap textarea::placeholder{color:#aab8b0;}

    .btn-send{
      width:44px;height:44px;border-radius:50%;border:none;
      background:linear-gradient(135deg,var(--g500),var(--g400));
      color:#fff;font-size:1.1rem;cursor:pointer;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 3px 12px rgba(37,136,63,.4);
      transition:all .25s;flex-shrink:0;
    }
    .btn-send:hover{background:linear-gradient(135deg,var(--g600),var(--g500));transform:scale(1.08);}

    /* typing indicator */
    .typing-indicator{display:flex;align-items:center;gap:4px;padding:10px 14px;background:#fff;border-radius:16px;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.12);width:fit-content;}
    .td{width:8px;height:8px;border-radius:50%;background:var(--g300);animation:td 1.4s infinite;}
    .td:nth-child(2){animation-delay:.2s;}
    .td:nth-child(3){animation-delay:.4s;}
    @keyframes td{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-6px);}}

    /* ══════════════════════════════════
       RIGHT SIDEBAR – Seller details
    ══════════════════════════════════ */
    .seller-sidebar{
      background:#fff;
      border-left:1.5px solid var(--border);
      display:flex;flex-direction:column;
      height:100vh;overflow-y:auto;
    }

    .ss-header{
      background:linear-gradient(135deg,var(--g900),var(--g800));
      padding:20px 18px;
      display:flex;flex-direction:column;align-items:center;gap:10px;
      border-bottom:2px solid var(--y400);
      flex-shrink:0;
    }
    .ss-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--y400),var(--y300));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:1.6rem;color:var(--g900);box-shadow:0 0 0 3px rgba(245,168,28,.3);}
    .ss-name{font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:#fff;text-align:center;}
    .ss-role{font-size:.72rem;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:4px;}
    .ss-verified{display:inline-flex;align-items:center;gap:4px;background:rgba(52,179,87,.2);border:1px solid rgba(52,179,87,.3);color:var(--g300);font-size:.68rem;font-weight:600;padding:2px 8px;border-radius:20px;}

    .ss-stats{display:flex;gap:0;border-bottom:1.5px solid var(--border);flex-shrink:0;}
    .ss-stat{flex:1;padding:14px 10px;text-align:center;border-right:1px solid var(--border);}
    .ss-stat:last-child{border-right:none;}
    .ss-num{font-family:'Syne',sans-serif;font-weight:800;font-size:1.05rem;color:var(--ink);}
    .ss-lbl{font-size:.65rem;color:var(--ink3);margin-top:2px;}

    .ss-section{padding:16px 18px;border-bottom:1.5px solid var(--border);}
    .ss-section-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ink3);margin-bottom:12px;display:flex;align-items:center;gap:6px;}
    .ss-section-label i{color:var(--g500);}
    .ss-row{display:flex;align-items:flex-start;gap:10px;padding:6px 0;font-size:.83rem;color:var(--ink2);}
    .ss-row i{color:var(--g400);font-size:.9rem;margin-top:1px;flex-shrink:0;}

    .stars{display:flex;gap:3px;color:var(--y400);font-size:.85rem;}
    .rating-text{font-size:.78rem;color:var(--ink3);margin-top:2px;}

    .ss-product-card{background:var(--g50);border:1.5px solid var(--border);border-radius:12px;overflow:hidden;cursor:pointer;transition:all .2s;margin-top:8px;}
    .ss-product-card:hover{border-color:var(--g300);box-shadow:0 3px 12px rgba(10,40,20,.1);}
    .ss-product-img{height:110px;background:linear-gradient(135deg,var(--g50),var(--g100));display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--g200);overflow:hidden;}
    .ss-product-img img{width:100%;height:100%;object-fit:cover;}
    .ss-product-body{padding:10px 12px;}
    .ss-product-price{font-family:'Syne',sans-serif;font-weight:800;font-size:.95rem;color:var(--g600);}
    .ss-product-title{font-size:.79rem;color:var(--ink);margin-top:2px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}

    .btn-view-seller{width:calc(100% - 36px);margin:14px 18px;background:linear-gradient(135deg,var(--g500),var(--g400));border:none;color:#fff;border-radius:12px;padding:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;box-shadow:0 3px 12px rgba(37,136,63,.3);}
    .btn-view-seller:hover{background:linear-gradient(135deg,var(--g600),var(--g500));transform:translateY(-1px);}

    /* emoji panel */
    .emoji-panel{
      background:#fff;border-top:1.5px solid #ddd;
      padding:10px 14px;
      display:none;flex-wrap:wrap;gap:6px;
      max-height:120px;overflow-y:auto;
      font-size:1.3rem;
    }
    .emoji-panel.show{display:flex;}
    .emoji-btn{cursor:pointer;transition:transform .15s;padding:2px;}
    .emoji-btn:hover{transform:scale(1.3);}

    /* ══ ANIMATIONS ══ */
    @keyframes bubbleIn{
      from{opacity:0;transform:translateY(8px) scale(.95);}
      to{opacity:1;transform:translateY(0) scale(1);}
    }
    .msg-wrap{animation:bubbleIn .25s both;}

    @keyframes slideIn{from{opacity:0;transform:translateX(-10px);}to{opacity:1;transform:translateX(0);}}
    .conv-item{animation:slideIn .3s both;}

    /* scrollbar */
    ::-webkit-scrollbar{width:5px;height:5px;}
    ::-webkit-scrollbar-track{background:transparent;}
    ::-webkit-scrollbar-thumb{background:var(--g200);border-radius:5px;}

    @media(max-width:1100px){.seller-sidebar{display:none;}.app{grid-template-columns:320px 1fr;}}
    @media(max-width:700px){.conv-sidebar{display:none;}.app{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<div class="app">

  <!-- ══ LEFT SIDEBAR ══ -->
  <div class="conv-sidebar">

    <div class="cs-topbar">
      <div class="cs-logo">
        <div class="cs-logo-box"><i class="bi bi-shop-window"></i></div>
        Comercio<em>Local</em>
      </div>
      <div class="cs-spacer"></div>
      <div class="cs-top-actions">
        <button class="cs-icon-btn" title="Nuevo chat"><i class="bi bi-pencil-square"></i></button>
        <button class="cs-icon-btn" title="Opciones"><i class="bi bi-three-dots-vertical"></i></button>
      </div>
    </div>

    <div class="cs-search">
      <div class="cs-search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Buscar chats..." id="searchInput" oninput="filterChats()">
      </div>
    </div>

    <div class="cs-filters">
      <div class="cf-chip active" onclick="filterByType(this,'all')">Todos</div>
      <div class="cf-chip" onclick="filterByType(this,'unread')">No leídos</div>
      <div class="cf-chip" onclick="filterByType(this,'buying')">Comprando</div>
      <div class="cf-chip" onclick="filterByType(this,'selling')">Vendiendo</div>
    </div>

    <div class="conv-list" id="convList">
      <?php if (!empty($convList)): ?>
        <?php foreach ($convList as $idx => $c):
          $initial = strtoupper(mb_substr($c['other_user'], 0, 1));
          $isOnline = ($idx % 2 === 0); // alternate for demo
          $unread   = ($idx === 0) ? 2 : 0;
          $price    = !empty($c['product_price']) ? '$' . number_format($c['product_price'], 0, ',', '.') : '';
        ?>
          <div class="conv-item <?php echo ($c['id'] == $conversation_id) ? 'active' : ''; ?>"
               onclick="openChat(<?php echo $c['id']; ?>, '<?php echo addslashes($c['other_user']); ?>', '<?php echo addslashes($c['product_name']); ?>', '<?php echo $initial; ?>', <?php echo $isOnline ? 'true' : 'false'; ?>, '<?php echo addslashes($price); ?>')"
               data-name="<?php echo htmlspecialchars($c['other_user']); ?>"
               id="conv-<?php echo $c['id']; ?>">
            <div class="ci-avatar">
              <?php echo $initial; ?>
              <div class="<?php echo $isOnline ? 'online-ring' : 'offline-ring'; ?>"></div>
            </div>
            <div class="ci-body">
              <div class="ci-top">
                <div class="ci-name"><?php echo htmlspecialchars($c['other_user']); ?></div>
                <div class="ci-time">Ahora</div>
              </div>
              <div class="ci-bottom">
                <div class="ci-preview">Hola, ¿está disponible?</div>
                <?php if ($unread > 0): ?>
                  <div class="ci-unread"><?php echo $unread; ?></div>
                <?php endif; ?>
              </div>
              <div style="margin-top:3px;">
                <div class="ci-product-tag"><?php echo htmlspecialchars($c['product_name']); ?></div>
              </div>
            </div>
            <!-- HIDE BUTTON (appears on hover) -->
            <button class="ci-del-btn" title="Ocultar chat"
                    onclick="event.stopPropagation(); confirmDeleteChat(<?php echo $c['id']; ?>, '<?php echo addslashes($c['other_user']); ?>')">
              <i class="bi bi-eye-slash-fill"></i>
            </button>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="cs-empty">
          <i class="bi bi-chat-dots"></i>
          <p>No tienes conversaciones aún.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- User chip at bottom -->
    <div style="padding:12px 14px;border-top:1.5px solid var(--border);display:flex;align-items:center;gap:10px;background:var(--g50);">
      <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--y400),var(--y300));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:.88rem;color:var(--g900);flex-shrink:0;">
        <?php echo $userInitial; ?>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:.84rem;font-weight:600;color:var(--ink);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><?php echo htmlspecialchars($user['full_name']); ?></div>
        <div style="font-size:.7rem;color:var(--g500);">● En línea</div>
      </div>
      <a href="./inde.php" style="color:var(--ink3);font-size:.95rem;transition:color .2s;" title="Ir al inicio"><i class="bi bi-house-fill"></i></a>
    </div>

  </div>

  <!-- ══ CENTER CHAT ══ -->
  <div class="chat-window">

    <!-- Chat empty state (shown when no conversation selected) -->
    <div class="chat-empty" id="chatEmpty">
      <div class="ce-icon"><i class="bi bi-chat-dots-fill"></i></div>
      <div class="ce-title">ComercioLocal Mensajes</div>
      <div class="ce-sub">Selecciona una conversación para empezar a chatear con compradores y vendedores de tu ciudad.</div>
      <div class="ce-badge"><i class="bi bi-shield-check-fill"></i> Mensajes seguros y cifrados</div>
    </div>

    <!-- Chat active (hidden until conversation selected) -->
    <div id="chatActive" style="display:none;flex-direction:column;height:100%;">

      <!-- Header -->
      <div class="chat-header">
        <button class="ch-back" onclick="closeChat()"><i class="bi bi-arrow-left"></i></button>
        <div class="ch-avatar" id="chAvatar" onclick="toggleRightPanel()">
          <span id="chAvatarInitial">?</span>
          <div class="ch-av-online" id="chOnlineDot"></div>
        </div>
        <div class="ch-info">
          <div class="ch-name" id="chName">–</div>
          <div class="ch-status">
            <div class="ch-status-dot" id="chStatusDot"></div>
            <span id="chStatusText">En línea</span>
          </div>
        </div>
        <div class="ch-actions">
          <button class="ch-action-btn" title="Buscar"><i class="bi bi-search"></i></button>
          <button class="ch-action-btn" title="Videollamada"><i class="bi bi-camera-video"></i></button>
          <button class="ch-action-btn" title="Llamada"><i class="bi bi-telephone"></i></button>
          <button class="ch-action-btn" title="Más opciones"><i class="bi bi-three-dots-vertical"></i></button>
        </div>
      </div>

      <!-- Product pin -->
      <div class="product-pin" id="productPin">
        <div class="pp-img" id="ppImg"><i class="bi bi-box-seam"></i></div>
        <div class="pp-info">
          <div class="pp-label">Producto en discusión</div>
          <div class="pp-title" id="ppTitle">–</div>
          <div class="pp-price" id="ppPrice"></div>
        </div>
        <button class="pp-btn" onclick="window.open('./actions/detalleProducto.php', '_blank')">
          <i class="bi bi-eye-fill"></i> Ver
        </button>
      </div>

      <!-- Messages -->
      <div class="messages-area" id="messages">
        <!-- populated by JS -->
      </div>

      <!-- Emoji panel -->
      <div class="emoji-panel" id="emojiPanel">
        <?php
        $emojis = ['😊','😂','❤️','👍','🙏','🔥','💯','✅','🎉','😎','🤔','👀','💰','📦','🏷️','🚀','⭐','🛒','🤝','💬'];
        foreach ($emojis as $e) echo "<span class='emoji-btn' onclick=\"insertEmoji('$e')\">$e</span>";
        ?>
      </div>

      <!-- Input bar -->
      <div class="msg-input-bar">
        <div class="input-side-btns">
          <button class="input-btn" onclick="toggleEmoji()" title="Emojis"><i class="bi bi-emoji-smile"></i></button>
          <button class="input-btn" title="Adjuntar" onclick="document.getElementById('attachInput').click()">
            <i class="bi bi-paperclip"></i>
          </button>
          <input type="file" id="attachInput" accept="image/*" style="display:none" onchange="handleImageAttach(this)">
        </div>
        <div class="input-field-wrap">
          <textarea id="msg" rows="1" placeholder="Escribe un mensaje..." onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        </div>
        <!-- LOGIC PRESERVED: onclick="sendMessage()" -->
        <button class="btn-send" id="btnSend" onclick="sendMessage()">
          <i class="bi bi-send-fill"></i>
        </button>
      </div>
    </div>

  </div>

  <!-- ══ RIGHT SIDEBAR – Seller info ══ -->
  <div class="seller-sidebar" id="sellerSidebar">

    <div class="ss-header">
      <div class="ss-avatar" id="ssAvatar">?</div>
      <div class="ss-name" id="ssName">Vendedor</div>
      <div class="ss-role"><i class="bi bi-circle-fill" style="font-size:.5rem;color:var(--g400);"></i> En línea ahora</div>
      <div class="ss-verified"><i class="bi bi-patch-check-fill"></i> Verificado</div>
    </div>

    <div class="ss-stats">
      <div class="ss-stat"><div class="ss-num">98%</div><div class="ss-lbl">Respuesta</div></div>
      <div class="ss-stat"><div class="ss-num">4.8</div><div class="ss-lbl">Rating</div></div>
      <div class="ss-stat"><div class="ss-num">47</div><div class="ss-lbl">Ventas</div></div>
    </div>

    <div class="ss-section">
      <div class="ss-section-label"><i class="bi bi-info-circle-fill"></i> Información</div>
      <div class="ss-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
      <div class="ss-row"><i class="bi bi-clock-fill"></i> Miembro desde 2023</div>
      <div class="ss-row"><i class="bi bi-shield-check-fill"></i> Identidad verificada</div>
      <div style="margin-top:10px;">
        <div class="stars">
          <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
        </div>
        <div class="rating-text">4.8 de 5 · 24 reseñas</div>
      </div>
    </div>

    <div class="ss-section">
      <div class="ss-section-label"><i class="bi bi-box-seam-fill"></i> Producto en discusión</div>
      <div class="ss-product-card" id="ssProdCard">
        <div class="ss-product-img" id="ssProdImg"><i class="bi bi-box-seam"></i></div>
        <div class="ss-product-body">
          <div class="ss-product-price" id="ssProdPrice">–</div>
          <div class="ss-product-title" id="ssProdTitle">–</div>
        </div>
      </div>
    </div>

    <div class="ss-section">
      <div class="ss-section-label"><i class="bi bi-lightning-charge-fill"></i> Respuestas rápidas</div>
      <div style="display:flex;flex-direction:column;gap:7px;">
        <button onclick="sendQuickReply('¿Está disponible?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">💬 "¿Está disponible?"</button>
        <button onclick="sendQuickReply('¿Acepta ofertas?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">💰 "¿Acepta ofertas?"</button>
        <button onclick="sendQuickReply('¿Cuándo podemos vernos?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">📅 "¿Cuándo podemos vernos?"</button>
      </div>
    </div>

    <button class="btn-view-seller" onclick="alert('Ver perfil del vendedor')">
      <i class="bi bi-person-circle"></i> Ver perfil del vendedor
    </button>

  </div>

</div><!-- /app -->

<!-- ══ HIDE CHAT MODAL ══ -->
<div class="del-modal-overlay" id="delModal">
  <div class="del-modal">
    <div class="del-modal-icon" style="background:#fff3e0;">
      <i class="bi bi-eye-slash-fill" style="color:#d48c0a;"></i>
    </div>
    <div class="del-modal-title">¿Ocultar conversación?</div>
    <div class="del-modal-sub">
      Vas a ocultar el chat con
      <strong id="delModalName">este usuario</strong>.
      Desaparecerá de tu lista pero <strong>volverá automáticamente</strong>
      cuando recibas un nuevo mensaje.
    </div>
    <div class="del-modal-btns">
      <button class="del-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>
      <button class="del-modal-confirm" id="delModalConfirmBtn"
              onclick="executeDeleteChat()"
              style="background:#d48c0a;">
        <i class="bi bi-eye-slash-fill"></i> Ocultar
      </button>
    </div>
  </div>
</div>

<!-- ══ TOAST ══ -->
<div class="del-toast" id="delToast">
  <i class="bi bi-check-circle-fill"></i>
  Chat ocultado. Volverá si te escriben.
</div>

<script>
  /* ═══════════════════════════════════════
     LOGIC PRESERVED — original JS + extras
  ═══════════════════════════════════════ */
  let socket         = null;
  let conversation_id = <?php echo $conversation_id ? intval($conversation_id) : 'null'; ?>;
  const USER_ID       = <?php echo intval($user_id); ?>;
  let typingTimer     = null;
  let typingEl        = null;

  /* ── Helpers ── */
  function getTime() {
    const now = new Date();
    return now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
  }

  function getDateLabel() {
    const d = new Date();
    const days = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const diff = 0; // today
    if (diff === 0) return 'Hoy';
    if (diff === 1) return 'Ayer';
    return days[d.getDay()];
  }

  function addDateSep(label) {
    const sep = document.createElement('div');
    sep.className = 'date-sep';
    sep.innerHTML = `<span>${label}</span>`;
    document.getElementById('messages').appendChild(sep);
  }

  /* ── LOGIC PRESERVED: addMessage ── */
  function addMessage(text, isMe, isImage = false, customTime = null, senderName = null) {
    const msgs = document.getElementById('messages');

    const wrap = document.createElement('div');
    wrap.className = 'msg-wrap ' + (isMe ? 'me' : 'other');

    const time   = customTime || getTime();
    const ticks  = isMe ? '<div class="bubble-ticks read"><i class="bi bi-check-all"></i></div>' : '';

    if (isImage) {
      wrap.innerHTML = `
        <div class="bubble ${isMe ? 'me' : 'other'}" style="padding:5px;">
          <div class="bubble-img"><img src="${text}" alt="imagen" loading="lazy"></div>
          <div class="bubble-meta"><span class="bubble-time">${time}</span>${ticks}</div>
        </div>`;
    } else {
      wrap.innerHTML = `
        <div class="bubble ${isMe ? 'me' : 'other'}">
          <div class="bubble-text">${text}</div>
          <div class="bubble-meta"><span class="bubble-time">${time}</span>${ticks}</div>
        </div>`;
    }

    msgs.appendChild(wrap);
    wrap.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }

  /* Typing indicator */
  function showTyping() {
    if (typingEl) return;
    const msgs = document.getElementById('messages');
    const wrap = document.createElement('div');
    wrap.className = 'msg-wrap other';
    wrap.innerHTML = `<div class="typing-indicator"><div class="td"></div><div class="td"></div><div class="td"></div></div>`;
    msgs.appendChild(wrap);
    typingEl = wrap;
    wrap.scrollIntoView({ behavior:'smooth', block:'end' });
  }
  function hideTyping() { if (typingEl) { typingEl.remove(); typingEl = null; } }

  /* Auto responses (demo) */
  const autoReplies = [
    '¡Claro! Todavía está disponible 😊',
    'Sí, podemos negociar el precio.',
    'Te puedo enviar más fotos si quieres.',
    '¿Cuándo te quedaría bien vernos?',
    'El producto está en perfecto estado.',
    '¡Listo! Con gusto te lo aparto.',
  ];

  function triggerAutoReply() {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
      showTyping();
      setTimeout(() => {
        hideTyping();
        const reply = autoReplies[Math.floor(Math.random() * autoReplies.length)];
        addMessage(reply, false);
      }, 1800);
    }, 600);
  }

  /* ── LOGIC PRESERVED: openChat ── */
  function openChat(id, otherUser, productName, initial, isOnline, price) {
    conversation_id = id;

    /* show chat panel */
    document.getElementById('chatEmpty').style.display = 'none';
    const active = document.getElementById('chatActive');
    active.style.display = 'flex';

    /* mark active in sidebar */
    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
    const convEl = document.getElementById('conv-' + id);
    if (convEl) { convEl.classList.add('active'); const badge = convEl.querySelector('.ci-unread'); if (badge) badge.remove(); }

    /* update header */
    document.getElementById('chAvatarInitial').textContent = initial;
    document.getElementById('chName').textContent = otherUser;
    const onlineDot  = document.getElementById('chOnlineDot');
    const statusText = document.getElementById('chStatusText');
    onlineDot.style.background  = isOnline ? 'var(--g400)' : '#ccc';
    statusText.textContent = isOnline ? 'En línea' : 'Visto recientemente';

    /* update right sidebar */
    document.getElementById('ssAvatar').textContent = initial;
    document.getElementById('ssName').textContent   = otherUser;

    /* product pin */
    document.getElementById('ppTitle').textContent  = productName;
    document.getElementById('ppPrice').textContent  = price || '';
    document.getElementById('ssProdTitle').textContent = productName;
    document.getElementById('ssProdPrice').textContent = price || '';

    /* clear messages, add date separator + demo messages */
    const msgs = document.getElementById('messages');
    msgs.innerHTML = '';
    addDateSep(getDateLabel());

    /* Load history via AJAX — LOGIC PRESERVED */
    fetch(`get_messages.php?conversation_id=${id}`)
      .then(res => res.json())
      .then(data => {
        if (data && data.length) {
          // Use is_me and time_label from improved get_messages.php
          data.forEach(msg => {
            addMessage(msg.message, msg.is_me, false, msg.time_label, msg.sender_name);
          });
        } else {
          /* Demo messages if no history */
          addMessage('Hola! Estoy interesado en tu producto 👋', false);
          addMessage('¡Hola! Claro, con mucho gusto. ¿Qué quieres saber?', true);
          addMessage('¿Está disponible para verlo esta semana?', false);
          /* quick replies */
          const qr = document.createElement('div');
          qr.className = 'quick-replies';
          qr.innerHTML = `
            <span class="qr-pill" onclick="sendQuickReply('Sí, claro! ¿Cuándo te queda bien?')">Sí, disponible</span>
            <span class="qr-pill" onclick="sendQuickReply('Puedo el sábado en la tarde.')">El sábado</span>
            <span class="qr-pill" onclick="sendQuickReply('Te envío las fotos ahora mismo.')">Enviar fotos</span>`;
          msgs.appendChild(qr);
        }
      })
      .catch(() => {
        /* No endpoint yet: show demo */
        addMessage('Hola! ¿Está disponible este producto?', false);
        addMessage('Sí, claro. ¿Cuándo quieres verlo?', true);
      });

    /* enable input */
    document.getElementById('msg').disabled  = false;
    document.getElementById('btnSend').disabled = false;
    document.getElementById('msg').placeholder = 'Escribe un mensaje...';
    document.getElementById('msg').focus();

    /* close old socket, open new — LOGIC PRESERVED */
    if (socket) socket.close();
    try {
      socket = new WebSocket("ws://localhost:8080/chat");
      socket.onopen = () => {
        socket.send(JSON.stringify({ type:"init", user_id:USER_ID, conversation_id:id }));
      };
      socket.onmessage = (event) => {
        const data = JSON.parse(event.data);
        addMessage(data.message, data.sender_id == USER_ID);
      };
    } catch(e) {
      console.warn('WebSocket not available (demo mode)');
    }
  }

  function closeChat() {
    document.getElementById('chatEmpty').style.display = 'flex';
    document.getElementById('chatActive').style.display = 'none';
    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
  }

  /* ── LOGIC PRESERVED + ENHANCED: sendMessage ── */
  function sendMessage() {
    const input = document.getElementById('msg');
    const msg   = input.value.trim();
    if (!msg || !conversation_id) return;

    // Try WebSocket first
    if (socket && socket.readyState === WebSocket.OPEN) {
      socket.send(JSON.stringify({
        type: 'message',
        message: msg,
        sender_id: USER_ID,
        conversation_id: conversation_id
      }));
    }

    // Always persist via HTTP (WebSocket server also should persist,
    // but this ensures delivery even without a WS server running)
    fetch('save_message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id, message: msg })
    }).catch(err => console.warn('save_message error:', err));

    addMessage(msg, true);
    input.value = '';
    input.style.height = 'auto';

    // update sidebar preview
    const convEl = document.getElementById('conv-' + conversation_id);
    if (convEl) {
      const prev = convEl.querySelector('.ci-preview');
      if (prev) prev.textContent = msg;
      const t = convEl.querySelector('.ci-time');
      if (t) t.textContent = getTime();
    }

    triggerAutoReply();
  }

  /* Quick reply helper */
  function sendQuickReply(msg) {
    document.getElementById('msg').value = msg;
    sendMessage();
  }

  /* ── LOGIC PRESERVED: auto-open if URL param ── */
  window.onload = () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('conversation_id');
    if (id) {
      const convEl = document.getElementById('conv-' + id);
      if (convEl) convEl.click();
    }
  };

  /* ── Extra UI ── */
  function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  }

  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
  }

  function toggleEmoji() {
    document.getElementById('emojiPanel').classList.toggle('show');
  }

  function insertEmoji(emoji) {
    const input = document.getElementById('msg');
    input.value += emoji;
    input.focus();
  }

  function handleImageAttach(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => addMessage(ev.target.result, true, true);
    reader.readAsDataURL(file);
    input.value = '';
  }

  function toggleRightPanel() {
    const panel = document.getElementById('sellerSidebar');
    panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
  }

  function filterChats() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(item => {
      const name = item.dataset.name?.toLowerCase() || '';
      item.style.display = name.includes(q) ? 'flex' : 'none';
    });
  }

  function filterByType(el, type) {
    document.querySelectorAll('.cf-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
  }

  /* ══════════════════════════════════
     SIDEBAR POLLING
     Consulta cada 5s si hay conversaciones
     nuevas o reactivadas (ej: alguien escribe
     en un chat que el otro había eliminado).
  ══════════════════════════════════ */
  let _knownConvIds = new Set(
    [...document.querySelectorAll('.conv-item')].map(el => el.id.replace('conv-',''))
  );

  function buildConvItem(c) {
    const isOnline   = Math.random() > 0.5; // en producción usar presencia real
    const onlineClass = isOnline ? 'online-ring' : 'offline-ring';
    const unreadBadge = c.unread_count > 0
      ? `<div class="ci-unread">${c.unread_count}</div>` : '';
    const price = c.product_price
      ? '$' + Number(c.product_price).toLocaleString('es-CO') : '';

    return `
      <div class="conv-item"
           onclick="openChat(${c.id},'${c.other_user.replace(/'/g,"\\'")}','${c.product_name.replace(/'/g,"\\'")}','${c.initial}',${isOnline},'${price}')"
           data-name="${c.other_user}"
           id="conv-${c.id}"
           style="animation:slideIn .35s both;">
        <div class="ci-avatar">
          ${c.initial}
          <div class="${onlineClass}"></div>
        </div>
        <div class="ci-body">
          <div class="ci-top">
            <div class="ci-name">${c.other_user}</div>
            <div class="ci-time">${c.time_label}</div>
          </div>
          <div class="ci-bottom">
            <div class="ci-preview">${c.last_message || 'Sin mensajes aún'}</div>
            ${unreadBadge}
          </div>
          <div style="margin-top:3px;">
            <div class="ci-product-tag">${c.product_name}</div>
          </div>
        </div>
        <button class="ci-del-btn" title="Ocultar chat"
                onclick="event.stopPropagation();confirmDeleteChat(${c.id},'${c.other_user.replace(/'/g,"\\'")}')" >
          <i class="bi bi-eye-slash-fill"></i>
        </button>
      </div>`;
  }

  function pollConversations() {
    fetch('get_conversations.php')
      .then(r => r.json())
      .then(list => {
        if (!Array.isArray(list)) return;

        const convList = document.getElementById('convList');

        list.forEach(c => {
          const idStr = String(c.id);

          if (!_knownConvIds.has(idStr)) {
            // ── NUEVA o REACTIVADA: insertar al tope ──
            _knownConvIds.add(idStr);

            // Quitar empty state si estaba
            const empty = convList.querySelector('.cs-empty');
            if (empty) empty.remove();

            // Insertar al inicio de la lista
            convList.insertAdjacentHTML('afterbegin', buildConvItem(c));

            // Notificación visual si no es el chat activo
            if (c.id !== conversation_id && c.unread_count > 0) {
              showToast(`💬 Nuevo mensaje de ${c.other_user}`);
            }

          } else {
            // ── EXISTENTE: actualizar preview y hora ──
            const el      = document.getElementById('conv-' + c.id);
            if (!el) return;
            const preview = el.querySelector('.ci-preview');
            const time    = el.querySelector('.ci-time');
            if (preview && c.last_message) preview.textContent = c.last_message;
            if (time    && c.time_label)   time.textContent    = c.time_label;

            // actualizar badge de no leídos
            let badge = el.querySelector('.ci-unread');
            if (c.unread_count > 0 && c.id !== conversation_id) {
              if (!badge) {
                const bottom = el.querySelector('.ci-bottom');
                if (bottom) {
                  badge = document.createElement('div');
                  badge.className = 'ci-unread';
                  bottom.appendChild(badge);
                }
              }
              if (badge) badge.textContent = c.unread_count;
            } else if (badge) {
              badge.remove();
            }
          }
        });

        // Detectar conversaciones eliminadas por el servidor
        // (si ya no vienen en la lista, removerlas)
        _knownConvIds.forEach(idStr => {
          if (!list.find(c => String(c.id) === idStr)) {
            _knownConvIds.delete(idStr);
            // No removemos del DOM aquí porque puede ser que la eliminó
            // este mismo usuario y ya fue animada por executeDeleteChat()
          }
        });
      })
      .catch(() => {}); // silenciar errores de red
  }

  // Arrancar polling cada 5 segundos
  setInterval(pollConversations, 5000);

  /* close emoji on click outside */
  document.addEventListener('click', e => {
    if (!e.target.closest('.emoji-panel') && !e.target.closest('.input-btn')) {
      document.getElementById('emojiPanel').classList.remove('show');
    }
  });

  /* ══════════════════════════════════
     DELETE CHAT LOGIC
  ══════════════════════════════════ */
  let _pendingDeleteId   = null;
  let _pendingDeleteName = null;

  function confirmDeleteChat(convId, otherUser) {
    _pendingDeleteId   = convId;
    _pendingDeleteName = otherUser;
    document.getElementById('delModalName').textContent = otherUser;
    document.getElementById('delModal').classList.add('show');
  }

  function closeDeleteModal() {
    document.getElementById('delModal').classList.remove('show');
    _pendingDeleteId   = null;
    _pendingDeleteName = null;
  }

  /* Close modal clicking outside */
  document.getElementById('delModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
  });

  /* Close modal with Escape */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
  });

  function showToast(msg) {
    const toast = document.getElementById('delToast');
    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${msg}`;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3200);
  }

  function executeDeleteChat() {
    if (!_pendingDeleteId) return;

    const btn  = document.getElementById('delModalConfirmBtn');
    const id   = _pendingDeleteId;
    const name = _pendingDeleteName;

    /* Loading state */
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Ocultando...';
    btn.disabled  = true;

    fetch('eliminar_chat.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ conversation_id: id })
    })
    .then(res => res.json())
    .then(data => {
      if (data.ok) {
        /* Animate out of sidebar */
        const el = document.getElementById('conv-' + id);
        if (el) {
          el.style.transition = 'opacity .3s, transform .3s';
          el.style.opacity    = '0';
          el.style.transform  = 'translateX(-20px)';
          setTimeout(() => el.remove(), 300);
        }

        /* If this was the active chat, reset to empty state */
        if (conversation_id === id) {
          closeChat();
          conversation_id = null;
        }

        closeDeleteModal();
        showToast(`💬 Chat con ${name} ocultado. Volverá si te escriben.`);

      } else {
        alert('Error: ' + (data.error || 'No se pudo ocultar'));
        btn.innerHTML = '<i class="bi bi-eye-slash-fill"></i> Ocultar';
        btn.disabled  = false;
      }
    })
    .catch(() => {
      alert('Error de conexión. Intenta de nuevo.');
      btn.innerHTML = '<i class="bi bi-eye-slash-fill"></i> Ocultar';
      btn.disabled  = false;
    });
  }
</script>

</body>
</html>