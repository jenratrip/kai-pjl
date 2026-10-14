        /* =====================================================
           DATA KA DARI DATABASE LARAVEL
        ===================================================== */

        function getKAByStatus(status) {

            const idElemen = {
                BATAL: "kaBatal",
                JALAN: "kaBerjalan",
                BELUM: "kaBelumLewat"
            };

            const element = document.getElementById(idElemen[status]);

            if (!element) {
                return [];
            }

            try {
                const data = JSON.parse(element.dataset.ka || "[]");
                return Array.isArray(data) ? data : [];
            } catch (error) {
                console.error("Gagal membaca data KA dari Laravel:", error);
                return [];
            }
        }


        /* =====================================================
           FORMAT NOMOR KA
        ===================================================== */

        function formatNomorKA(ka) {

            const nomor = String(ka?.nomor ?? "").trim();

            if (!nomor || nomor === "-") {
                return "";
            }

            return /^KA\s*/i.test(nomor) ? nomor : "KA " + nomor;
        }


        /* =====================================================
           RENDER DATA KA
        ===================================================== */

        function renderDataKA() {

            const daftar = [
                {
                    id: "kaBatal",
                    status: "BATAL",
                    kosong: "Tidak ada KA yang batal."
                },
                {
                    id: "kaBerjalan",
                    status: "JALAN",
                    kosong: "Tidak ada KA yang sedang berjalan."
                },
                {
                    id: "kaBelumLewat",
                    status: "BELUM",
                    kosong: "Tidak ada KA yang belum lewat."
                }
            ];

            daftar.forEach(function(item) {
                const element = document.getElementById(item.id);

                if (!element) {
                    return;
                }

                const data = getKAByStatus(item.status);
                const nomorKA = data.map(formatNomorKA).filter(Boolean);

                element.textContent = nomorKA.length
                    ? nomorKA.join(", ")
                    : item.kosong;

                element.classList.toggle("auto-ka-empty", nomorKA.length === 0);
            });
        }


        /* =====================================================
           MENU MOBILE
        ===================================================== */

        function toggleMobileMenu() {

            const menu =
                document.getElementById("mobileMenu");

            const overlay =
                document.getElementById("menuOverlay");

            const button =
                document.getElementById("menuButton");

            if (!menu || !overlay || !button) {
                return;
            }

            const isOpen =
                menu.classList.contains("show");

            if (isOpen) {

                closeMobileMenu();

                return;
            }

            menu.classList.add("show");

            overlay.classList.add("show");

            document.body.classList.add(
                "menu-open"
            );

            button.setAttribute(
                "aria-expanded",
                "true"
            );
        }


        function closeMobileMenu() {

            const menu =
                document.getElementById("mobileMenu");

            const overlay =
                document.getElementById("menuOverlay");

            const button =
                document.getElementById("menuButton");

            if (!menu || !overlay || !button) {
                return;
            }

            menu.classList.remove("show");

            overlay.classList.remove("show");

            document.body.classList.remove(
                "menu-open"
            );

            button.setAttribute(
                "aria-expanded",
                "false"
            );
        }


        document.addEventListener(
            "keydown",
            function(event) {

                if (event.key === "Escape") {
                    closeMobileMenu();
                }

            }
        );


        /* =====================================================
           SECURITY
        ===================================================== */

        function toggleSecurity(button) {

            const parent =
                button.parentElement;

            if (!parent) {
                return;
            }

            const buttons =
                parent.querySelectorAll(".toggle");

            buttons.forEach(
                function(btn) {

                    btn.classList.remove("active");

                    btn.textContent =
                        btn.textContent.replace(
                            /^✓\s*/,
                            ""
                        );

                }
            );

            button.classList.add("active");

            button.textContent =
                "✓ " +
                button.textContent.trim();
        }

        /* =====================================================
            SINKRONISASI HARI, TANGGAL, DAN JAM
            ==================================================== */

            function sinkronisasiTanggalWaktu() {
                const tanggalInput = document.getElementById("tanggal");
                const jamInput = document.getElementById("jamSerahTerima");
                const hariSelect = document.getElementById("hari");

                if (!tanggalInput || !jamInput || !hariSelect) {
                    return;
                }

                // Gunakan tanggal dan waktu zona WIB dari server Laravel.
                const config = document.getElementById("serahTerimaConfig");

if (!config?.dataset.serverTime) {
    console.error("Waktu server Laravel tidak ditemukan.");
    return;
}

const waktuServer = new Date(config.dataset.serverTime);

                const tahun = waktuServer.getFullYear();
                const bulan = String(waktuServer.getMonth() + 1).padStart(2, "0");
                const tanggal = String(waktuServer.getDate()).padStart(2, "0");

                tanggalInput.value = `${tahun}-${bulan}-${tanggal}`;

                jamInput.value =
                    String(waktuServer.getHours()).padStart(2, "0") + ":" +
                    String(waktuServer.getMinutes()).padStart(2, "0");

                const namaHari = [
                    "Minggu",
                    "Senin",
                    "Selasa",
                    "Rabu",
                    "Kamis",
                    "Jumat",
                    "Sabtu"
                ];

                hariSelect.value = namaHari[waktuServer.getDay()];
            }


        /* =====================================================
           INISIALISASI CANVAS
        ===================================================== */

        document
            .querySelectorAll(".signature-canvas")
            .forEach(function(canvas) {

                const ctx =
                    canvas.getContext("2d");

                const signatureArea =
                    canvas.closest(".signature-area");

                if (!signatureArea) {
                    return;
                }

                let drawing = false;


                function setupCanvas() {

                    ctx.lineWidth = 2;

                    ctx.lineCap = "round";

                    ctx.lineJoin = "round";

                    ctx.strokeStyle = "#1e3156";
                }


                signatureArea.addEventListener(
                    "click",
                    function() {

                        if (
                            signatureArea.classList.contains(
                                "show-result"
                            )
                        ) {
                            return;
                        }

                        if (
                            !signatureArea.classList.contains(
                                "active"
                            )
                        ) {

                            signatureArea.classList.add(
                                "active"
                            );

                            const placeholder =
                                signatureArea.querySelector(
                                    ".signature-placeholder"
                                );

                            if (placeholder) {

                                placeholder.style.display =
                                    "none";
                            }
                        }
                    }
                );


                function resizeCanvas() {

                    const rect =
                        canvas.getBoundingClientRect();

                    if (
                        rect.width === 0 ||
                        rect.height === 0
                    ) {
                        return;
                    }

                    let oldCanvas = null;

                    if (
                        canvas.width > 0 &&
                        canvas.height > 0
                    ) {

                        oldCanvas =
                            document.createElement(
                                "canvas"
                            );

                        oldCanvas.width =
                            canvas.width;

                        oldCanvas.height =
                            canvas.height;

                        const oldCtx =
                            oldCanvas.getContext(
                                "2d"
                            );

                        oldCtx.drawImage(
                            canvas,
                            0,
                            0
                        );
                    }

                    canvas.width =
                        rect.width;

                    canvas.height =
                        rect.height;

                    setupCanvas();

                    if (oldCanvas) {

                        ctx.drawImage(
                            oldCanvas,
                            0,
                            0,
                            oldCanvas.width,
                            oldCanvas.height,
                            0,
                            0,
                            canvas.width,
                            canvas.height
                        );
                    }
                }


                resizeCanvas();

                window.addEventListener(
                    "resize",
                    resizeCanvas
                );


                function getPosition(event) {

                    const rect =
                        canvas.getBoundingClientRect();

                    return {

                        x:
                            event.clientX -
                            rect.left,

                        y:
                            event.clientY -
                            rect.top

                    };
                }


                canvas.addEventListener(
                    "pointerdown",
                    function(event) {

                        if (
                            !signatureArea.classList.contains(
                                "active"
                            )
                        ) {
                            return;
                        }

                        drawing = true;

                        canvas.setPointerCapture(
                            event.pointerId
                        );

                        const position =
                            getPosition(event);

                        ctx.beginPath();

                        ctx.moveTo(
                            position.x,
                            position.y
                        );
                    }
                );


                canvas.addEventListener(
                    "pointermove",
                    function(event) {

                        if (!drawing) {
                            return;
                        }

                        const position =
                            getPosition(event);

                        ctx.lineTo(
                            position.x,
                            position.y
                        );

                        ctx.stroke();
                    }
                );


                function stopDrawing(event) {

                    drawing = false;

                    if (
                        event &&
                        canvas.hasPointerCapture(
                            event.pointerId
                        )
                    ) {

                        canvas.releasePointerCapture(
                            event.pointerId
                        );
                    }
                }


                canvas.addEventListener(
                    "pointerup",
                    stopDrawing
                );

                canvas.addEventListener(
                    "pointercancel",
                    stopDrawing
                );

            });


        /* =====================================================
           CEK TANDA TANGAN
        ===================================================== */

        function hasSignature(canvas) {

            if (!canvas) {
                return false;
            }

            const ctx =
                canvas.getContext("2d");

            const pixels =
                ctx.getImageData(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                ).data;

            for (
                let i = 3;
                i < pixels.length;
                i += 4
            ) {

                if (pixels[i] > 0) {
                    return true;
                }
            }

            return false;
        }


        /* =====================================================
           BUAT NAMA FILE PNG
        ===================================================== */

        function getSignatureFileName(card) {

            const nameElement =
                card.querySelector(".signature-name");

            let name = "tanda-tangan";

            if (nameElement) {

                const text =
                    nameElement.innerText || "";

                const match =
                    text.match(
                        /Nama:\s*(.+)/i
                    );

                if (
                    match &&
                    match[1]
                ) {

                    name =
                        match[1].trim();

                }
            }

            name =
                name
                    .replace(
                        /[^a-zA-Z0-9\s-]/g,
                        ""
                    )
                    .trim()
                    .replace(
                        /\s+/g,
                        "-"
                    )
                    .toLowerCase();

            if (!name) {
                name = "tanda-tangan";
            }

            return "tanda-tangan-" + name + ".png";
        }


        /* =====================================================
           SIMPAN TANDA TANGAN
        ===================================================== */

        function saveSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }

            const canvas =
                card.querySelector(
                    ".signature-canvas"
                );

            const signatureArea =
                card.querySelector(
                    ".signature-area"
                );

            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );

            const placeholder =
                card.querySelector(
                    ".signature-placeholder"
                );

            const downloadButton =
                card.querySelector(
                    ".signature-download"
                );

            if (
                !canvas ||
                !signatureArea ||
                !signatureResult
            ) {
                return;
            }


            /* CEK TANDA TANGAN */

            if (!hasSignature(canvas)) {

                alert(
                    "Silakan buat tanda tangan terlebih dahulu."
                );

                return;
            }


            /* BUAT PNG */

            const signatureImage =
                canvas.toDataURL(
                    "image/png"
                );


            /* TAMPILKAN HASIL */

            signatureResult.src =
                signatureImage;

            signatureResult.dataset.signature = signatureImage;
            signatureResult.dataset.action = "set";


            /* UBAH TAMPILAN */

            signatureArea.classList.remove(
                "active"
            );

            signatureArea.classList.add(
                "show-result",
                "saved"
            );


            if (placeholder) {

                placeholder.style.display =
                    "none";
            }


            canvas.dataset.saved =
                "true";


            /* TAMPILKAN DOWNLOAD */

            if (downloadButton) {

                downloadButton.style.display =
                    "inline-flex";
            }


            alert(
                "Tanda tangan berhasil disimpan."
            );
        }


        /* =====================================================
           DOWNLOAD TANDA TANGAN PNG
        ===================================================== */

        function downloadSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }


            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );


            if (!signatureResult) {

                alert(
                    "Tanda tangan belum tersedia."
                );

                return;
            }


            const imageData =
                signatureResult.dataset.signature;


            if (!imageData) {

                alert(
                    "Silakan simpan tanda tangan terlebih dahulu."
                );

                return;
            }


            const fileName =
                getSignatureFileName(card);


            const link =
                document.createElement("a");


            link.href =
                imageData;

            link.download =
                fileName;


            document.body.appendChild(link);

            link.click();

            document.body.removeChild(link);
        }


        /* =====================================================
           HAPUS TANDA TANGAN
        ===================================================== */

        function clearSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }

            const canvas =
                card.querySelector(
                    ".signature-canvas"
                );

            const signatureArea =
                card.querySelector(
                    ".signature-area"
                );

            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );

            const placeholder =
                card.querySelector(
                    ".signature-placeholder"
                );

            const downloadButton =
                card.querySelector(
                    ".signature-download"
                );


            if (
                !canvas ||
                !signatureArea
            ) {
                return;
            }


            const ctx =
                canvas.getContext("2d");


            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );


            if (signatureResult) {

                signatureResult.src = "";

                signatureResult.dataset.signature = "";
                signatureResult.dataset.action = "clear";
            }


            signatureArea.classList.remove(
                "active",
                "saved",
                "show-result"
            );


            canvas.dataset.saved =
                "false";


            if (placeholder) {

                placeholder.style.display =
                    "block";
            }


            /* SEMBUNYIKAN DOWNLOAD */

            if (downloadButton) {

                downloadButton.style.display =
                    "none";
            }
        }


        /* =====================================================
           DATA PJL PENERIMA
        ===================================================== */

        function updateDataPjlPenerima() {

            const namaInput =
                document.getElementById(
                    "namaPjlPenerima"
                );

            const nipInput =
                document.getElementById(
                    "nipPjlPenerima"
                );

            const dataPenerima =
                document.getElementById(
                    "dataPjlPenerima"
                );

            if (
                !namaInput ||
                !nipInput ||
                !dataPenerima
            ) {
                return;
            }

            const nama =
                namaInput.value.trim() || "-";

            const nip =
                nipInput.value.trim() || "-";

            dataPenerima.textContent = "";

            const namaText =
                document.createTextNode(
                    "Nama: " + nama
                );

            const br =
                document.createElement("br");

            const nipText =
                document.createTextNode(
                    "NIP: " + nip
                );

            dataPenerima.appendChild(namaText);
            dataPenerima.appendChild(br);
            dataPenerima.appendChild(nipText);
        }


        /* =====================================================
           SIMPAN DOKUMEN
        ===================================================== */
        async function saveDocument() {
            const tombolSimpan = document.querySelector(
                '.button.button-primary[onclick="saveDocument()"]'
            );

            if (!tombolSimpan) {
                alert("Tombol Simpan tidak ditemukan.");
                return;
            }

            const cards = document.querySelectorAll(".signature-card");

            if (cards.length < 2) {
                alert("Bagian tanda tangan tidak ditemukan.");
                return;
            }

            // Ambil aksi dan data tanda tangan dari masing-masing kartu.
            // "keep" mempertahankan file lama, "set" menyimpan tanda tangan baru,
            // dan "clear" menghapus tanda tangan lama.
            const getSignature = (card) => {
                const image = card.querySelector(".signature-result");
                const action = image?.dataset.action || "keep";

                return {
                    action,
                    data: action === "set"
                        ? (image?.dataset.signature || null)
                        : null
                };
            };

            const tandaTanganPenyerah = getSignature(cards[0]);
            const tandaTanganPenerima = getSignature(cards[1]);

            // Ambil nama dan NIP dari input yang tersedia.
            const nipPenyerahInput = cards[0].querySelector("input");
            const namaPenerimaInput = document.getElementById(
                "namaPjlPenerima"
            );
            const nipPenerimaInput = document.getElementById(
                "nipPjlPenerima"
            );

            // Kumpulkan inventaris.
            const inventaris = Array.from(
                document.querySelectorAll(".inventory-row")
            ).map((row) => {
                const jumlah = row.querySelector(".qty");
                const kondisi = row.querySelectorAll(".condition");

                return {
                    nama: row.querySelector(".inventory-name")
                        ?.textContent.trim() || "",
                    jumlah: jumlah?.value || "0",
                    kelengkapan: kondisi[0]?.value || "",
                    kondisi: kondisi[1]?.value || ""
                };
            });

            // Ambil ringkasan KA yang ditampilkan di halaman.
            const ringkasanKA = {
                batal: document.getElementById("kaBatal")?.textContent.trim() || "",
                berjalan: document.getElementById("kaBerjalan")?.textContent.trim() || "",
                belumLewat: document.getElementById("kaBelumLewat")?.textContent.trim() || ""
            };

            const payload = {
            tanggal_serah_terima:
                document.getElementById("tanggal")?.value || "",

            jam_serah_terima:
                document.getElementById("jamSerahTerima")?.value || "",

            catatan_penting:
                document.getElementById("catatanPenting")?.value || "",

                ppka:
                    document.getElementById("ppka")?.value || "",

                nama_penerima:
                    namaPenerimaInput?.value.trim() || "",

                nip_penerima:
                    nipPenerimaInput?.value.trim() || "",

                nip_penyerah:
                    nipPenyerahInput?.value.trim() || "",

                ringkasan: {
                    inventaris: inventaris,
                    kereta_api: ringkasanKA
                },

                tanda_tangan_penyerah: tandaTanganPenyerah.data,
tanda_tangan_penerima: tandaTanganPenerima.data,

tanda_tangan_penyerah_action: tandaTanganPenyerah.action,
tanda_tangan_penerima_action: tandaTanganPenerima.action
            };

            // Ambil token CSRF Laravel.
            const csrfToken = document.querySelector(
                'meta[name="csrf-token"]'
            )?.getAttribute("content");

            if (!csrfToken) {
                alert(
                    "Token keamanan tidak ditemukan. Pastikan tag meta CSRF tersedia di bagian head."
                );
                return;
            }

            tombolSimpan.disabled = true;
            const teksAsli = tombolSimpan.textContent;
            tombolSimpan.textContent = "Menyimpan...";

            try {
                const config = document.getElementById("serahTerimaConfig");
const storeUrl = config?.dataset.storeUrl;

if (!storeUrl) {
    throw new Error("URL penyimpanan dokumen tidak ditemukan.");
}

const response = await fetch(
    storeUrl,
    {
        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-CSRF-TOKEN": csrfToken
                        },
                        body: JSON.stringify(payload)
                    }
                );

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422 && result.errors) {
                        const pesan = Object.values(result.errors)
                            .flat()
                            .join("\n");

                        throw new Error(
                            pesan || result.message || "Data belum valid."
                        );
                    }

                    throw new Error(
                        result.message || "Gagal menyimpan dokumen."
                    );
                }

                alert(
                    result.message ||
                    "Dokumen serah terima berhasil disimpan."
                );

            } catch (error) {
                console.error("Gagal menyimpan serah terima:", error);

                alert(
                    "Dokumen gagal disimpan.\n" +
                    (error.message || "Periksa koneksi dan log Laravel.")
                );
            } finally {
                tombolSimpan.disabled = false;
                tombolSimpan.textContent = teksAsli;
            }
        }



        /* =====================================================
           INISIALISASI HALAMAN
        ===================================================== */

        document.addEventListener("DOMContentLoaded", function() {
                sinkronisasiTanggalWaktu();

                renderDataKA();
                updateDataPjlPenerima();

                const namaPenerima = document.getElementById("namaPjlPenerima");
                const nipPenerima = document.getElementById("nipPjlPenerima");

                if (namaPenerima) {
                    namaPenerima.addEventListener("input", updateDataPjlPenerima);
                }

                if (nipPenerima) {
                    nipPenerima.addEventListener("input", updateDataPjlPenerima);
                }
            });

        /* =====================================================
           UPDATE SAAT KEMBALI KE TAB
        ===================================================== */

        window.addEventListener(
            "focus",
            function() {

                renderDataKA();

            }
        );


        /* =====================================================
           UPDATE VISIBILITY
        ===================================================== */

        document.addEventListener(
            "visibilitychange",
            function() {

                if (!document.hidden) {

                    renderDataKA();

                }

            }
        );

    
