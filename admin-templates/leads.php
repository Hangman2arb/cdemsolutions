            <div class="admin-title-bar">
                <h1 class="admin-title">Leads</h1>
            </div>

            <!-- Filters -->
            <div class="admin-filters">
                <a href="/admin/leads/" class="filter-tab <?= empty($status) ? 'active' : '' ?>">All</a>
                <?php foreach (['new', 'contacted', 'qualified', 'closed', 'archived'] as $s): ?>
                <a href="/admin/leads/?status=<?= $s ?>" class="filter-tab <?= $status === $s ? 'active' : '' ?>">
                    <?= ucfirst($s) ?>
                    <?php if (!empty($statusCounts[$s])): ?>
                    <span class="filter-tab__count"><?= $statusCounts[$s] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>

                <form class="admin-search" method="GET" action="/admin/leads/">
                    <?php if ($status): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search leads...">
                    <button type="submit" class="btn btn--small btn--primary">Search</button>
                </form>
            </div>

            <!-- Table -->
            <?php if (empty($leads)): ?>
            <div class="admin-card"><p class="admin-card__empty">No leads found.</p></div>
            <?php else: ?>
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><a href="/admin/leads/<?= $lead['id'] ?>/"><?= htmlspecialchars($lead['name']) ?></a></td>
                            <td><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a></td>
                            <td><?= htmlspecialchars(mb_strimwidth($lead['subject'], 0, 40, '...')) ?></td>
                            <td>
                                <form method="POST" action="/admin/leads/status/" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/leads/?<?= http_build_query(['status' => $status, 'search' => $search, 'page' => $pagination['current']]) ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select status-select--<?= $lead['status'] ?>">
                                        <?php foreach (['new', 'contacted', 'qualified', 'closed', 'archived'] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $lead['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= date('M j, Y', strtotime($lead['created_at'])) ?></td>
                            <td>
                                <a href="/admin/leads/<?= $lead['id'] ?>/" class="btn btn--small btn--outline-dark">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="admin-pagination">
                <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <a href="/admin/leads/?page=<?= $p ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>"
                   class="admin-pagination__link <?= $p === $pagination['current'] ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
