document.addEventListener("DOMContentLoaded", function () {
    updateFileList();

    let fileInput = document.getElementById("fileUploadInput");
    if (fileInput) {
        // Hapus event listener yang ada untuk mencegah duplikasi
        fileInput.removeEventListener("change", uploadFile);
        // Tambahkan event listener baru
        fileInput.addEventListener("change", function(e) {
            // Prevent the event from firing multiple times
            e.preventDefault();
            uploadFile();
        });
    } else {
        console.error("Elemen dengan ID 'fileUploadInput' tidak ditemukan.");
    }
});

function showCreateFolderModal() {
    var myModal = new bootstrap.Modal(document.getElementById('createFolderModal'));
    myModal.show();
}    

function updateFileList() {
    const fileContainer = document.getElementById("fileContainer");
    if (!fileContainer) {
        console.error("❌ Elemen fileContainer tidak ditemukan!");
        return;
    }

    // Mapping ekstensi ke ikon
    const icons = {
        pdf: 'https://upload.wikimedia.org/wikipedia/commons/8/87/PDF_file_icon.svg',
        doc: 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/DOC_icon_bold.svg/640px-DOC_icon_bold.svg.png',
        docx: 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/DOC_icon_bold.svg/640px-DOC_icon_bold.svg.png',
        xls: 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Xls_icon_%282000-03%29.svg/640px-Xls_icon_%282000-03%29.svg.png',
        xlsx: 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/15/Xls_icon_%282000-03%29.svg/640px-Xls_icon_%282000-03%29.svg.png',
        ppt: 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/.ppt_icon_%282000-03%29.svg/640px-.ppt_icon_%282000-03%29.svg.png',
        pptx: 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/ed/.ppt_icon_%282000-03%29.svg/640px-.ppt_icon_%282000-03%29.svg.png',
        jpg: 'https://upload.wikimedia.org/wikipedia/commons/4/47/JPEG_icon.svg',
        png: 'https://upload.wikimedia.org/wikipedia/commons/4/47/JPEG_icon.svg',
        txt: 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e8/.txt_OneDrive_icon.svg/640px-.txt_OneDrive_icon.svg.png',
    };
    const defaultIcon = 'https://upload.wikimedia.org/wikipedia/commons/b/bb/File-Document-icon.png';

    fetch("backend/get_files.php")
        .then(response => response.json())
        .then(data => {
            console.log("🔥 Data dari backend:", data.files);
            fileContainer.innerHTML = "";

            if (!data.files || !Array.isArray(Object.keys(data.files)) || data.files.length === 0) {
                console.warn("⚠ Tidak ada file yang diunggah.");
                fileContainer.innerHTML = "<p class='text-center'>Tidak ada file yang diunggah.</p>";
                return;
            }

            const params = new URLSearchParams(window.location.search);
            const dir = params.get("dir");
            console.log({dir});

            // Set untuk melacak folder yang sudah ditampilkan
            const displayedFolders = new Set();

            Object.keys(data.files).forEach(key => {
                const file = data.files[key];
                console.log("Processing:", {key, file});

                if (Array.isArray(file)) {
                    // Jika ini adalah array (folder) dan belum ditampilkan
                    if (!displayedFolders.has(key)) {
                        if (dir == null) {
                            // Tampilkan folder
                            const folderItem = `
                                <div class="col-md-2 mb-2">
                                    <div class="card">
                                        <a href="lpb.php?dir=${key}">
                                            <img src="https://img.icons8.com/?size=100&id=WWogVNJDSfZ5&format=png&color=000000" class="card-img-top" style="height:50px; object-fit:contain;">
                                        </a>
                                        <div class="card-body text-center">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <p class="card-text mb-0">${key}</p>
                                                <div class="dropdown">
                                                    <button class="btn btn-light border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <div class="px-3">
                                                                <input type="text" id="rename-${key}" class="form-control form-control-sm" placeholder="Rename" value="${key}">
                                                                <button onclick="renameFolder('${key}')" class="btn btn-sm btn-primary mt-2 w-100">Ubah Nama</button>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <button onclick="deleteFolder('${key}')" class="dropdown-item text-danger">Hapus</button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
                            fileContainer.innerHTML += folderItem;
                            displayedFolders.add(key);
                        } else if (dir === key) {
                            // Tampilkan isi folder
                            file.forEach(item => {
                                const subfile = item.replace("/"+key+"/", "");
                                const filePath = `uploads/${key}/${subfile}`.replace(/\/+/g, '/');
                                const ext = subfile.split('.').pop().toLowerCase();
                                const isImage = ['jpg', 'jpeg', 'png'].includes(ext);
                                const iconUrl = isImage ? filePath : (icons[ext] || defaultIcon);
                                const imgStyle = isImage ? "height:100px; object-fit:cover;" : "height:50px; object-fit:contain;";
                                const fileItem = `
                                    <div class="col-md-2 mb-2">
                                        <div class="card">
                                            <a href="${filePath}">
                                                <img src="${iconUrl}" class="card-img-top" style="${imgStyle}">
                                            </a>
                                            <div class="card-body text-center">
                                                <p class="card-text">${subfile.replace(/^\/+/, '')}</p>
                                                <button class="btn btn-sm btn-danger" onclick="deleteFile('${subfile}')">🗑 Hapus</button>
                                            </div>
                                        </div>
                                    </div>`;
                                fileContainer.innerHTML += fileItem;
                            });
                        }
                    }
                } else if (typeof file === "string" && dir == null) {
                    // Tampilkan file individual (bukan dalam folder)
                    const filePath = `uploads/${file.replace(/^\/+/, '')}`.replace(/\/+/g, '/');
                    const ext = file.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png'].includes(ext);
                    const iconUrl = isImage ? filePath : (icons[ext] || defaultIcon);
                    const imgStyle = isImage ? "height:100px; object-fit:cover;" : "height:50px; object-fit:contain;";
                    const fileItem = `
                        <div class="col-md-2 mb-2">
                            <div class="card">
                                <a href="${filePath}">
                                    <img src="${iconUrl}" class="card-img-top" style="${imgStyle}">
                                </a>
                                <div class="card-body text-center">
                                    <p class="card-text">${file.replace(/^\/+/, '')}</p>
                                    <button class="btn btn-sm btn-danger" onclick="deleteFile('${file}')">🗑 Hapus</button>
                                </div>
                            </div>
                        </div>`;
                    fileContainer.innerHTML += fileItem;
                }
            });
        })
        .catch(error => console.error("❌ Error fetching file list:", error));
}

async function uploadFile() {
    const fileInput = document.getElementById("fileUploadInput");

    if (!fileInput || !fileInput.files.length) {
        alert("Tidak ada file yang dipilih.");
        return;
    }

    // Disable input selama proses upload
    fileInput.disabled = true;

    const formData = new FormData();
    for (const file of fileInput.files) {
        formData.append("files[]", file);
    }

    const params = new URLSearchParams(window.location.search);
    const dir = params.get("dir");
    if (dir) {
        formData.append("target_dir", dir);
    }

    try {
        const response = await fetch('backend/upload.php', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        console.log("Raw response:", text);

        const data = JSON.parse(text);
        console.log("Parsed JSON:", data);

        alert(data.message);
        if (data.status === "success") {
            updateFileList(); // refresh file list
        }
    } catch (error) {
        console.error("Upload error:", error);
        alert("Terjadi kesalahan saat mengunggah file.");
    } finally {
        // Reset dan enable input setelah selesai
        fileInput.value = '';
        fileInput.disabled = false;
    }
}


function deleteFile(fileName) {
    let formData = new FormData();
    formData.append("filename", fileName);

    fetch("backend/delete_file.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert("File berhasil dihapus!");
                updateFileList(); // 🔄 Refresh daftar file
            } else {
                alert("Gagal menghapus file: " + data.message);
            }
        })
        .catch(error => console.error("Error deleting file:", error));
}

function downloadFile(fileName) {
    window.location.href = `backend/download.php?file=${fileName}`;
}

function createFolder() {
    let folderName = document.getElementById("folderName").value;
    if (folderName.trim() === "") {
        alert("Nama folder tidak boleh kosong");
        return;
    }

    let formData = new FormData();
    formData.append("folder_name", folderName);

    fetch("backend/create_folder.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            updateFileList();
        })
        .catch(error => console.error("Error creating folder:", error));
}

function uploadFolder() {
    let folderInput = document.getElementById("folderUploadInput");
    if (!folderInput || !folderInput.files.length) {
        alert("Tidak ada folder yang dipilih.");
        return;
    }

    let formData = new FormData();
    
    // Dapatkan nama folder utama dari path file pertama
    let mainFolderName = folderInput.files[0].webkitRelativePath.split('/')[0];
    
    for (let file of folderInput.files) {
        let relativePath = file.webkitRelativePath;
        console.log(`File: ${file.name}, Path: ${relativePath}`);
        formData.append("files[]", file);
        formData.append("paths[]", relativePath);
    }
    
    // Tambahkan flag untuk menandai ini adalah upload folder
    formData.append('upload_type', 'folder');

    fetch("backend/upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log("Server response:", text); // Debugging
        let data;
        try {
            data = JSON.parse(text);
        } catch (error) {
            console.error("Response bukan JSON:", text);
            alert("Terjadi kesalahan: Server tidak mengembalikan JSON yang valid.");
            return;
        }

        if (data.status === "success") {
            alert("Upload folder berhasil: " + data.message);
            updateFileList(); // Refresh daftar file setelah upload berhasil
        } else {
            alert("Upload gagal: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Upload gagal: " + error.message);
    });
}

// Tambahkan fungsi-fungsi baru untuk menangani rename dan delete
function renameFolder(oldName) {
    const newName = document.getElementById(`rename-${oldName}`).value.trim();
    if (!newName) {
        alert('Nama folder tidak boleh kosong');
        return;
    }

    const formData = new FormData();
    formData.append('oldname', oldName);
    formData.append('newname', newName);

    fetch('backend/rename.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
        updateFileList(); // Refresh tampilan setelah rename
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mengubah nama folder');
    });
}

function deleteFolder(folderName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus folder "${folderName}" dan semua isinya?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('filename', folderName);

    fetch('backend/delete_file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Folder berhasil dihapus!');
            updateFileList();
        } else {
            alert('Gagal menghapus folder: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal menghapus folder');
    });
}