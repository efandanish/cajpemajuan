<?php if (
    isset($_SESSION['user_role']) &&
    $_SESSION['user_role'] === 'admin'
): ?>

    <div class="modal fade" id="manageUserModal" tabindex="-1">

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <div>

                        <h5 class="modal-title fw-bold">
                            Pengurusan Pengguna
                        </h5>

                        <div class="small text-muted">
                            Jumlah Pengguna:
                            <?= count($users) ?>
                        </div>

                    </div>


                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>


                <div class="modal-body">

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Nama
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Peranan
                                    </th>

                                    <th class="text-end">
                                        Tindakan
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (!empty($users)): ?>

                                    <?php foreach ($users as $user): ?>

                                        <tr>

                                            <td class="fw-semibold">
                                                <?= htmlspecialchars($user['name']) ?>
                                            </td>


                                            <td>
                                                <?= htmlspecialchars($user['email']) ?>
                                            </td>


                                            <td>

                                                <?php if ($user['role'] === 'admin'): ?>

                                                    <span class="badge text-bg-primary">
                                                        Admin
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-info-subtle text-primary">
                                                        User
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <td class="text-end">

                                                <button type="button" class="btn btn-sm btn-outline-primary open-edit-user"
                                                    data-user-id="<?= $user['id'] ?>">
                                                    Edit
                                                </button>


                                                <?php if (
                                                    (int) $user['id']
                                                    !==
                                                    (int) $_SESSION['user_id']
                                                ): ?>

                                                    <form method="POST" action="/cajpemajuan/user/delete/<?= $user['id'] ?>"
                                                        class="delete-user-form d-inline">

                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Padam
                                                        </button>

                                                    </form>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                <?php else: ?>

                                    <tr>

                                        <td colspan="4" class="text-center text-muted py-4">
                                            Tiada pengguna direkodkan.
                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">
                        Tutup
                    </button>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>