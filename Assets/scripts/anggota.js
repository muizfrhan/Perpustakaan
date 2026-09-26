document.addEventListener("DOMContentLoaded", function () {
    const nimInput = document.getElementById("nim");
    const submitBtn = document.getElementById("submitBtn");
  
    nimInput.addEventListener("blur", function () {
      const nim = nimInput.value;
  
      if (nim.trim() !== "") {
        fetch(`Anggota/check_nim.php?nim=${nim}`)
          .then((response) => response.json())
          .then((data) => {
            if (data.exists) {
              alert("NIM sudah terdaftar. Harap masukkan NIM yang berbeda.");
              submitBtn.disabled = true; // Matikan tombol submit
            } else {
              submitBtn.disabled = false; // Aktifkan tombol submit
            }
          })
          .catch((error) => {
            console.error("Terjadi kesalahan:", error);
          });
      }
    });
  });
  