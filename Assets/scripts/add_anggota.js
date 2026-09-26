document.addEventListener("DOMContentLoaded", function () {
    const jurusanToKelas = {
        "D4 Teknologi Rekayasa Perangkat Lunak": ["23A1"],
        "S1 Teknik Informatika": ["23A1", "23A2", "23A3", "23A4", "23A5", "23A6"],
        "S1 Sistem Informasi": ["23A1", "23A2"],
        "D3 Teknik Komputer": ["23A1"]
    };

    const jurusanDropdown = document.getElementById("jurusan");
    const kelasDropdown = document.getElementById("kelas");

    jurusanDropdown.addEventListener("change", function () {
        const jurusan = this.value;

        // Reset kelas options
        kelasDropdown.innerHTML = '<option value="" disabled selected>Pilih Kelas</option>';

        if (jurusan in jurusanToKelas) {
            jurusanToKelas[jurusan].forEach(kelas => {
                const option = document.createElement("option");
                option.value = kelas;
                option.textContent = kelas;
                kelasDropdown.appendChild(option);
            });
        }
    });
});
