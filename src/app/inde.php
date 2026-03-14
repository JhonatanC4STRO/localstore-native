<?php include('../config/conexion.php');
?>
<!DOCTYPE html>
<html lang="eS">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../output.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

</head>

<body>
  <div class="">
    <?php include('../components/header.php'); ?>
    <?php include('../components/sliderhome.php'); ?>


    <main class="bg-red-200">
      <table class="">

        <thead>
          <tr>
            <th>Nombre</th>
            <th>E-Mail</th>
            <th>Contraseña</th>
          </tr>
        </thead>

        <tbody>


<?php

          $query = "SELECT * FROM users";
          $result_usuario = mysqli_query($conn, $query);

    
    while ($row = mysqli_fetch_assoc($result_usuario)) { ?>

            <tr>

            
              <td><?php echo $row['full_name']; ?></td>
              <td><?php echo $row['email']; ?></td>
              <td><?php echo $row['password']; ?></td>


              <td>
                <a href="edit.php?id=<?php echo $row['id']; ?>">Editar</a>
                <a href="delete.php?id=<?php echo $row['id']; ?>">Eliminar</a>
              </td>

            </tr>

          <?php } ?>

        </tbody>

      </table>

  </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

</body>

</html>