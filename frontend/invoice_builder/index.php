<?php
$conn = new mysqli("localhost","root","","invoice_db");
$templates = $conn->query("SELECT * FROM invoice_templates");
?>
<h2>Invoice Templates</h2>
<a href="editor.php">+ Create New Template</a>
<ul>
<?php while($row = $templates->fetch_assoc()){ ?>
  <li>
    <?php echo $row['name']; ?>
    <a href="editor.php?id=<?php echo $row['id']; ?>">Edit</a>
    <a href="preview.php?id=<?php echo $row['id']; ?>">Preview</a>
  </li>
<?php } ?>
</ul>
