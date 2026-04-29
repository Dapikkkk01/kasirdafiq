<?php
if (!isset($conn)) include "koneksi.php";
?>
<h3>Data Barang</h3>
<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Nama Barang</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Aksi</th>
    </tr>
<?php
$data = mysqli_query($conn, "SELECT * FROM barang");
if (mysqli_num_rows($data) > 0) {
    while ($d = mysqli_fetch_assoc($data)) {
        echo "<tr>";
        echo "<td>" . $d['id'] . "</td>";
        echo "<td>" . htmlspecialchars($d['nama_barang']) . "</td>";
        echo "<td>Rp " . number_format($d['harga'], 0, ',', '.') . "</td>";
        echo "<td>" . $d['stok'] . "</td>";
        echo "<td><a href='hapus_barang.php?id=" . $d['id'] . "' onclick=\"return confirm('Yakin hapus?')\">Hapus</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5'>Belum ada data barang.</td></tr>";
}
?>
</table>
