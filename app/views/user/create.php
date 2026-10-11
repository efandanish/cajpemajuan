<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>user">Kembali ke Senarai</a></div>
<div class="module-page">

    <div class="module-container">

        <div class="card">

            <form method="POST" action="<?= PUBLIC_ROOT ?>user/create" id="addUserForm">

                <div class="card-header">

                    <h5 class="card-title fw-bold">
                        Daftar Pengguna Baru
                    </h5>



                </div>


                <div class="card-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Nama Penuh
                        </label>

                        <input type="text" name="name" class="form-control" placeholder="cth: Ahmad Bin Ali" required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email" name="email" class="form-control" placeholder="cth: ahmad@mpkl.gov.my"
                            required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Kata Laluan
                        </label>

                        <input type="password" name="password" class="form-control" minlength="6" placeholder="******"
                            required>

                        <div class="form-text">
                            Sekurang-kurangnya 6 aksara.
                        </div>

                    </div>


                    <div>

                        <label class="form-label">
                            Peranan
                        </label>

                        <select name="role" class="form-select" required>

                            <option value="user">
                                Pengguna Biasa
                            </option>

                            <option value="admin">
                                Pentadbir (Admin)
                            </option>

                        </select>

                    </div>

                </div>


                <div class="card-footer">



                    <button type="submit" class="btn btn-primary">
                        Daftar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
