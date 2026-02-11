            <h1 class="admin-title">Dashboard</h1>

            <div class="stats-grid">
                <div class="stat-card stat-card--primary">
                    <div class="stat-card__number"><?= $stats['new_leads'] ?></div>
                    <div class="stat-card__label">New Leads</div>
                </div>
                <div class="stat-card stat-card--teal">
                    <div class="stat-card__number"><?= $stats['total_leads'] ?></div>
                    <div class="stat-card__label">Total Leads</div>
                </div>
                <div class="stat-card stat-card--orange">
                    <div class="stat-card__number"><?= $stats['published_posts'] ?></div>
                    <div class="stat-card__label">Published Posts</div>
                </div>
                <div class="stat-card stat-card--gray">
                    <div class="stat-card__number"><?= $stats['total_posts'] ?></div>
                    <div class="stat-card__label">Total Posts</div>
                </div>
            </div>

            <div class="admin-grid">
                <!-- Recent Leads -->
                <div class="admin-card">
                    <div class="admin-card__header">
                        <h2>Recent Leads</h2>
                        <a href="/admin/leads/" class="btn btn--small btn--outline-dark">View All</a>
                    </div>
                    <?php if (empty($stats['recent_leads'])): ?>
                    <p class="admin-card__empty">No leads yet.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['recent_leads'] as $lead): ?>
                        <tr>
                            <td><a href="/admin/leads/<?= $lead['id'] ?>/"><?= htmlspecialchars($lead['name']) ?></a></td>
                            <td><?= htmlspecialchars($lead['email']) ?></td>
                            <td><span class="badge badge--<?= $lead['status'] ?>"><?= ucfirst($lead['status']) ?></span></td>
                            <td><?= date('M j', strtotime($lead['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Recent Posts -->
                <div class="admin-card">
                    <div class="admin-card__header">
                        <h2>Recent Posts</h2>
                        <a href="/admin/blog/edit/new/" class="btn btn--small btn--primary">New Post</a>
                    </div>
                    <?php if (empty($stats['recent_posts'])): ?>
                    <p class="admin-card__empty">No posts yet.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>Title</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['recent_posts'] as $post): ?>
                        <tr>
                            <td><a href="/admin/blog/edit/<?= $post['id'] ?>/"><?= htmlspecialchars($post['title']) ?></a></td>
                            <td><span class="badge badge--<?= $post['status'] ?>"><?= ucfirst($post['status']) ?></span></td>
                            <td><?= date('M j', strtotime($post['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
