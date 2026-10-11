<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>user">Kembali ke Senarai</a></div>
        <div class="module-page">

            <div class="module-container">

                <div class="card">

                    <form method="POST" action="<?= PUBLIC_ROOT ?>user/update/<?= $user['id'] ?>" class="edit-user-form">

                        <div class="card-header">

                            <h5 class="card-title fw-bold">
                                Kemaskini Pengguna
                            </h5>



                        </div>


                        <div class="card-body">

                            <div class="mb-3">

                                <label class="form-label">
                                    Nama Penuh
                                </label>

                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($user['email']) ?>" required>

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Kata Laluan Baru
                                </label>

                                <input type="password" name="password" class="form-control" minlength="6"
                                    placeholder="Kosongkan jika tidak mahu menukar">

                                <div class="form-text">
                                    Sekurang-kurangnya 6 aksara jika ditukar.
                                </div>

                            </div>


                            <div>

                                <label class="form-label">
                                    Peranan
                                </label>


                                <?php if (
                                    (int) $user['id']
                                    ===
                                    (int) $_SESSION['user_id']
                                ): ?>

                                    <input type="hidden" name="role" value="<?= htmlspecialchars($user['role']) ?>">

                                    <select class="form-select" disabled>

                                        <option>
                                            Pentadbir (Admin)
                                        </option>

                                    </select>

                                    <div class="form-text">
                                        Anda tidak boleh menukar peranan akaun sendiri.
                                    </div>


                                <?php else: ?>

                                    <select name="role" class="form-select" required>

                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>
                                            >
                                            Pengguna Biasa
                                        </option>

                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>
                                            >
                                            Pentadbir (Admin)
                                        </option>

                                    </select>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="card-footer">



                            <button type="submit" class="btn btn-primary">
                                Simpan
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>
