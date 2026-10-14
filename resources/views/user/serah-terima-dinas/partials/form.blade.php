            <!-- HEADER DOKUMEN -->

            <header class="document-header">

                <div class="company">
                    PT KERETA API INDONESIA (PERSERO)
                </div>

                <div class="department">
                    DIREKTORAT KESELAMATAN DAN KEAMANAN
                </div>

                <h1 class="page-title">
                    Serah Terima Dinas PJL
                </h1>

                <div class="page-subtitle">
                    Dokumen kegiatan serah terima dinas PJL sebagai bukti kondisi operasional saat pergantian dinas.
                </div>

            </header>


            <!-- INFORMASI DINAS -->
            <div>
                <div class="field-label">HARI</div>
                <select class="select" id="hari">
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                    <option value="Minggu">Minggu</option>
                </select>
            </div>

            <div>
                <div class="field-label">TANGGAL</div>
                <input
                    type="date"
                    class="input"
                    id="tanggal"
                    value="{{ now('Asia/Jakarta')->format('Y-m-d') }}"
                >
            </div>

            <div>
                <div class="field-label">JAM SERAH TERIMA</div>
                <input
                    type="time"
                    class="input"
                    id="jamSerahTerima"
                    value="{{ now('Asia/Jakarta')->format('H:i') }}"
                >
            </div>


                <div class="info-grid-bottom">

                    <div>

                        <div class="field-label">
                            PJL MENYERAHKAN / DINAS SEBELUMNYA
                        </div>

                        <input
                            type="text"
                            class="input"
                            id="pjlMenyerahkan"
                            value="Pagi"
                        >

                    </div>


                    <div>

                        <div class="field-label">
                            PJL MENERIMA / DINAS BERIKUTNYA
                        </div>

                        <input
                            type="text"
                            class="input"
                            id="pjlMenerima"
                            value="Siang"
                        >

                    </div>

                </div>

            </div>


            <!-- 1. KA BATAL -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        1
                    </span>

                    KA-KA yang batal

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>BATAL</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBatal"
                    data-ka='@json($ringkasanKA["batal"] ?? [])'
                    aria-label="Daftar KA yang batal"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 2. KA BERJALAN -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        2
                    </span>

                    KA-KA Fak/PLB/KLB yang jalan

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>JALAN</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBerjalan"
                    data-ka='@json($ringkasanKA["berjalan"] ?? [])'
                    aria-label="Daftar KA yang berjalan"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 3. KA BELUM LEWAT -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        3
                    </span>

                    KA-KA yang belum lewat

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>BELUM</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBelumLewat"
                    data-ka='@json($ringkasanKA["belum"] ?? [])'
                    aria-label="Daftar KA yang belum lewat"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 4. ALAT PENGAMANAN -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        4
                    </span>

                    Alat pengamanan

                </div>

                <div class="security-list">

                    <div class="security-row">

                        <span>
                            Perangkat pengamanan
                        </span>

                        <div class="toggle-group">

                            <button
                                type="button"
                                class="toggle active"
                                onclick="toggleSecurity(this)"
                            >
                                ✓ Normal
                            </button>

                            <button
                                type="button"
                                class="toggle"
                                onclick="toggleSecurity(this)"
                            >
                                Tidak Normal
                            </button>

                        </div>

                    </div>


                    <div class="security-row">

                        <span>
                            Perangkat komunikasi
                        </span>

                        <div class="toggle-group">

                            <button
                                type="button"
                                class="toggle active"
                                onclick="toggleSecurity(this)"
                            >
                                ✓ Normal
                            </button>

                            <button
                                type="button"
                                class="toggle"
                                onclick="toggleSecurity(this)"
                            >
                                Tidak Normal
                            </button>

                        </div>

                    </div>

                </div>

            </section>
