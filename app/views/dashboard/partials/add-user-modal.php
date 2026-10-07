<div class="modal fade" id="addUserModal" tabindex="-1" data-bs-focus="false">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST" action="/cajpemajuan/user/create" id="addUserForm">

                <div class="modal-header">

                    <h5 class="modal-title fw-bold">
                        Daftar Pengguna Baru
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>


                <div class="modal-body">

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


                <div class="modal-footer">

                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Daftar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>