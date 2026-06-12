<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/base.php';
require_once 'includes/header.php';

// Harus login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 303 See Other');
    header('Location: http://localhost/findit/auth/login.php');
    exit;
}

$barang_id  = isset($_GET['barang_id']) ? (int)$_GET['barang_id'] : 0;
$lawan_id   = isset($_GET['dengan'])    ? (int)$_GET['dengan']    : 0;
$user_id    = (int)$_SESSION['user_id'];
$user_nama  = $_SESSION['user_nama'] ?? '';

if ($barang_id <= 0 || $lawan_id <= 0 || $lawan_id === $user_id) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Ambil data barang
$stmt = $conn->prepare("SELECT * FROM barang WHERE id = ?");
$stmt->bind_param("i", $barang_id);
$stmt->execute();
$barang = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$barang) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Ambil data lawan bicara
$stmt = $conn->prepare("SELECT id, nama FROM users WHERE id = ?");
$stmt->bind_param("i", $lawan_id);
$stmt->execute();
$lawan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lawan) {
    header('Location: http://localhost/findit/laporan.php');
    exit;
}

// Pastikan user boleh chat:
// hanya pemilik laporan atau user lain yang login (bukan bot)
$is_pemilik = $barang['user_id'] == $user_id;
$is_lawan   = $barang['user_id'] == $lawan_id;

// Minimal salah satu harus pemilik barang
if (!$is_pemilik && !$is_lawan) {
    header('Location: http://localhost/findit/detail.php?id=' . $barang_id);
    exit;
}

// Tandai pesan dari lawan sebagai sudah dibaca
$upd = $conn->prepare("UPDATE pesan SET dibaca=1
                        WHERE barang_id=? AND sender_id=? AND receiver_id=? AND dibaca=0");
$upd->bind_param("iii", $barang_id, $lawan_id, $user_id);
$upd->execute();
$upd->close();

// Ambil semua pesan di thread ini
$stmt = $conn->prepare("
    SELECT p.*, u.nama AS sender_nama
    FROM pesan p
    JOIN users u ON u.id = p.sender_id
    WHERE p.barang_id = ?
      AND (
        (p.sender_id = ? AND p.receiver_id = ?)
        OR
        (p.sender_id = ? AND p.receiver_id = ?)
      )
    ORDER BY p.created_at ASC
");
$stmt->bind_param("iiiii", $barang_id, $user_id, $lawan_id, $lawan_id, $user_id);
$stmt->execute();
$pesan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<div class="content" style="margin-top:1.5rem;padding-bottom:0">

  <!-- Header chat -->
  <div style="
    background:#fff;
    border:1.5px solid #E5E7EB;
    border-radius:16px 16px 0 0;
    padding:14px 18px;
    display:flex;
    align-items:center;
    gap:12px;
  ">
    <a href="http://localhost/findit/detail.php?id=<?= $barang_id ?>"
       style="color:#6B7280;text-decoration:none;font-size:18px;line-height:1">←</a>

    <!-- Avatar -->
    <div style="
      width:38px;height:38px;
      background:linear-gradient(135deg,#3B82F6,#1D4ED8);
      border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      color:#fff;font-weight:700;font-size:15px;
      flex-shrink:0;
    ">
      <?= mb_strtoupper(mb_substr($lawan['nama'], 0, 1)) ?>
    </div>

    <div style="flex:1;min-width:0">
      <div style="font-weight:700;font-size:14px;color:#111;
                  white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        <?= htmlspecialchars($lawan['nama']) ?>
      </div>
      <div style="font-size:11px;color:#9CA3AF;margin-top:1px;
                  white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
        Re: <?= htmlspecialchars($barang['nama']) ?>
      </div>
    </div>

    <a href="http://localhost/findit/detail.php?id=<?= $barang_id ?>"
       style="
         font-size:11px;color:#185FA5;text-decoration:none;
         background:#EFF6FF;border:1px solid #BFDBFE;
         padding:4px 10px;border-radius:20px;white-space:nowrap;
       ">
      Lihat laporan
    </a>
  </div>

  <!-- Area pesan -->
  <div id="chat-area" style="
    background:#F9FAFB;
    border-left:1.5px solid #E5E7EB;
    border-right:1.5px solid #E5E7EB;
    padding:16px;
    height:420px;
    overflow-y:auto;
    display:flex;
    flex-direction:column;
    gap:10px;
  ">
    <?php if (empty($pesan_list)): ?>
      <div id="empty-chat" style="
        flex:1;display:flex;flex-direction:column;
        align-items:center;justify-content:center;
        color:#9CA3AF;text-align:center;
      ">
        <div style="font-size:36px;margin-bottom:8px">💬</div>
        <div style="font-size:13px">Belum ada pesan</div>
        <div style="font-size:12px;margin-top:4px">
          Mulai percakapan dengan <?= htmlspecialchars($lawan['nama']) ?>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($pesan_list as $p):
        $is_mine = $p['sender_id'] == $user_id;
        $waktu   = date('H:i', strtotime($p['created_at']));
        $tgl     = date('d M', strtotime($p['created_at']));
      ?>
        <div style="
          display:flex;
          flex-direction:column;
          align-items:<?= $is_mine ? 'flex-end' : 'flex-start' ?>;
        ">
          <div style="
            max-width:72%;
            background:<?= $is_mine ? 'linear-gradient(135deg,#2563EB,#1D4ED8)' : '#fff' ?>;
            color:<?= $is_mine ? '#fff' : '#111' ?>;
            border:<?= $is_mine ? 'none' : '1.5px solid #E5E7EB' ?>;
            border-radius:<?= $is_mine ? '16px 16px 4px 16px' : '16px 16px 16px 4px' ?>;
            padding:10px 14px;
            font-size:13px;
            line-height:1.55;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
            word-break:break-word;
          ">
            <?= nl2br(htmlspecialchars($p['isi'])) ?>
          </div>
          <div style="font-size:10px;color:#9CA3AF;margin-top:3px;padding:0 4px">
            <?= $tgl ?> · <?= $waktu ?>
            <?php if ($is_mine): ?>
              · <?= $p['dibaca'] ? '✓✓' : '✓' ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Input pesan -->
  <div style="
    background:#fff;
    border:1.5px solid #E5E7EB;
    border-top:none;
    border-radius:0 0 16px 16px;
    padding:12px 14px;
    display:flex;
    gap:10px;
    align-items:flex-end;
  ">
    <textarea id="input-pesan"
              placeholder="Tulis pesan..."
              rows="1"
              style="
                flex:1;
                border:1.5px solid #E5E7EB;
                border-radius:12px;
                padding:10px 14px;
                font-size:13px;
                font-family:inherit;
                resize:none;
                outline:none;
                transition:border-color .15s;
                max-height:100px;
                overflow-y:auto;
                line-height:1.5;
              "
              onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();kirimPesan()}"
              oninput="autoResize(this)"
              onfocus="this.style.borderColor='#3B82F6'"
              onblur="this.style.borderColor='#E5E7EB'"
    ></textarea>
    <button onclick="kirimPesan()" id="btn-kirim" style="
      background:linear-gradient(135deg,#2563EB,#1D4ED8);
      color:#fff;
      border:none;
      border-radius:12px;
      width:42px;height:42px;
      display:flex;align-items:center;justify-content:center;
      cursor:pointer;
      flex-shrink:0;
      font-size:17px;
      transition:opacity .15s;
    " onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
      ➤
    </button>
  </div>

</div>

<script>
const USER_ID    = <?= $user_id ?>;
const BARANG_ID  = <?= $barang_id ?>;
const LAWAN_ID   = <?= $lawan_id ?>;
let   lastCount  = <?= count($pesan_list) ?>;

// Scroll ke bawah
function scrollBawah() {
  const area = document.getElementById('chat-area');
  area.scrollTop = area.scrollHeight;
}
scrollBawah();

// Auto resize textarea
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

// Kirim pesan
async function kirimPesan() {
  const input = document.getElementById('input-pesan');
  const isi   = input.value.trim();
  if (!isi) return;

  const btn = document.getElementById('btn-kirim');
  btn.disabled = true;
  btn.style.opacity = '.5';

  try {
    const res  = await fetch('http://localhost/findit/api/kirim_pesan.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        barang_id:   BARANG_ID,
        receiver_id: LAWAN_ID,
        isi:         isi
      })
    });
    const data = await res.json();

    if (data.ok) {
      input.value = '';
      input.style.height = 'auto';
      // Hapus empty state kalau ada
      const empty = document.getElementById('empty-chat');
      if (empty) empty.remove();
      // Tambah bubble baru langsung (optimistic)
      tambahBubble(data.pesan);
      scrollBawah();
      lastCount++;
    } else {
      alert(data.error || 'Gagal mengirim pesan');
    }
  } catch(e) {
    alert('Koneksi bermasalah, coba lagi');
  }

  btn.disabled = false;
  btn.style.opacity = '1';
}

// Render bubble pesan
function tambahBubble(p) {
  const area   = document.getElementById('chat-area');
  const isMine = p.sender_id == USER_ID;
  const waktu  = p.created_at ? p.created_at.substring(11,16) : '--:--';
  const tgl    = p.created_at ? p.created_at.substring(8,10) + ' ' + bulan(p.created_at.substring(5,7)) : '';

  const wrap = document.createElement('div');
  wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isMine?'flex-end':'flex-start'}`;
  wrap.innerHTML = `
    <div style="
      max-width:72%;
      background:${isMine?'linear-gradient(135deg,#2563EB,#1D4ED8)':'#fff'};
      color:${isMine?'#fff':'#111'};
      border:${isMine?'none':'1.5px solid #E5E7EB'};
      border-radius:${isMine?'16px 16px 4px 16px':'16px 16px 16px 4px'};
      padding:10px 14px;font-size:13px;line-height:1.55;
      box-shadow:0 1px 4px rgba(0,0,0,.06);word-break:break-word;
    ">${escHtml(p.isi).replace(/\n/g,'<br>')}</div>
    <div style="font-size:10px;color:#9CA3AF;margin-top:3px;padding:0 4px">
      ${tgl} · ${waktu}${isMine?' · ✓':''}
    </div>
  `;
  area.appendChild(wrap);
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function bulan(m) {
  return ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'][parseInt(m,10)];
}

// Polling pesan baru setiap 5 detik
setInterval(async () => {
  try {
    const res  = await fetch(`http://localhost/findit/api/ambil_pesan.php?barang_id=${BARANG_ID}&dengan=${LAWAN_ID}&offset=${lastCount}`);
    const data = await res.json();
    if (data.pesan && data.pesan.length > 0) {
      const empty = document.getElementById('empty-chat');
      if (empty) empty.remove();
      data.pesan.forEach(p => { tambahBubble(p); lastCount++; });
      scrollBawah();
    }
  } catch(e) {}
}, 5000);
</script>

<?php require_once 'includes/footer.php'; ?>