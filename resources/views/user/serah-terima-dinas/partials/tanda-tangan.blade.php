            <!-- 8. TANDA TANGAN -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        8
                    </span>

                    Tanda tangan

                </div>


                <div class="signature-grid">


                    <!-- PJL MENYERAHKAN -->

                    <div class="signature-card">

                        <div class="signature-title">
                            PJL yang menyerahkan / meninggalkan dinas
                        </div>

                        <div class="signature-subtitle">
                            Silakan lengkapi data dan tanda tangan.
                        </div>


                        <div class="signature-input">

                            <div class="field-label">
                                NIP
                            </div>

                            <input
                                type="text"
                                class="input"
                                value="199208242015031002"
                            >

                        </div>


                        <div
                            class="field-label"
                            style="margin-top:8px;"
                        >
                            Tanda tangan
                        </div>


                        
                        <div class="signature-area {{ $serahTerima?->tanda_tangan_penyerah ? 'show-result saved' : '' }}">

                            <canvas class="signature-canvas"></canvas>

                            <img
                                class="signature-result"
                                src="{{ $serahTerima?->tanda_tangan_penyerah ? asset('storage/' . $serahTerima->tanda_tangan_penyerah) : '' }}"
                                data-action="keep"
                                alt="Hasil tanda tangan PJL menyerahkan"
                            >

                            <span class="signature-placeholder">
                                Klik di sini untuk tanda tangan
                            </span>

                        </div>



                        <div class="signature-actions">

                            <button
                                type="button"
                                class="signature-save"
                                onclick="saveSignature(this)"
                            >
                                Simpan
                            </button>

                            <button
                                type="button"
                                class="signature-clear"
                                onclick="clearSignature(this)"
                            >
                                Hapus
                            </button>

                            <button
                                type="button"
                                class="signature-download"
                                onclick="downloadSignature(this)"
                                style="display:none;"
                            >
                                Download PNG
                            </button>

                        </div>


                        <div class="signature-name">
                            Nama: Budi Santoso
                            <br>
                            NIP: 199208242015031002
                        </div>

                    </div>


                    <!-- PJL MENERIMA -->

                    <div class="signature-card">

                        <div class="signature-title">
                            PJL yang menerima / menggantikan dinas
                        </div>

                        <div class="signature-subtitle">
                            Pastikan data PJL berikutnya sudah benar.
                        </div>


                        <div class="signature-input">

                            <div class="field-label">
                                Nama PJL Penerima
                            </div>

                            <input
                                type="text"
                                class="input pjl-penerima-nama"
                                id="namaPjlPenerima"
                                placeholder="Masukkan nama PJL penerima"
                                autocomplete="off"
                            >

                            <div
                                class="field-label"
                                style="margin-top:8px;"
                            >
                                NIP
                            </div>

                            <input
                                type="text"
                                class="input pjl-penerima-nip"
                                id="nipPjlPenerima"
                                placeholder="Masukkan NIP PJL penerima"
                                inputmode="numeric"
                                autocomplete="off"
                            >

                        </div>


                        <div
                            class="field-label"
                            style="margin-top:8px;"
                        >
                            Tanda tangan
                        </div>


                        <div class="signature-area {{ $serahTerima?->tanda_tangan_penerima ? 'show-result saved' : '' }}">

                            <canvas class="signature-canvas"></canvas>

                            <img
                                class="signature-result"
                                src="{{ $serahTerima?->tanda_tangan_penerima ? asset('storage/' . $serahTerima->tanda_tangan_penerima) : '' }}"
                                data-action="keep"
                                alt="Hasil tanda tangan PJL menerima"
                            >

                            <span class="signature-placeholder">
                                Klik di sini untuk tanda tangan
                            </span>

                        </div>


                        <div class="signature-actions">

                            <button
                                type="button"
                                class="signature-save"
                                onclick="saveSignature(this)"
                            >
                                Simpan
                            </button>

                            <button
                                type="button"
                                class="signature-clear"
                                onclick="clearSignature(this)"
                            >
                                Hapus
                            </button>

                            <button
                                type="button"
                                class="signature-download"
                                onclick="downloadSignature(this)"
                                style="display:none;"
                            >
                                Download PNG
                            </button>

                        </div>


                        <div class="signature-name" id="dataPjlPenerima">
                            Nama: -
                            <br>
                            NIP: -
                        </div>

                    </div>

                </div>

            </section>
