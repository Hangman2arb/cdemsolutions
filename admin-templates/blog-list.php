            <div class="admin-title-bar">
                <h1 class="admin-title">Blog Posts</h1>
                <a href="/admin/blog/edit/new/" class="btn btn--primary btn--small">New Post</a>
            </div>

            <div class="admin-filters">
                <a href="/admin/blog/" class="filter-tab <?= empty($status) ? 'active' : '' ?>">All</a>
                <a href="/admin/blog/?status=published" class="filter-tab <?= $status === 'published' ? 'active' : '' ?>">Published</a>
                <a href="/admin/blog/?status=draft" class="filter-tab <?= $status === 'draft' ? 'active' : '' ?>">Drafts</a>
            </div>

            <?php if (empty($posts)): ?>
            <div class="admin-card"><p class="admin-card__empty">No posts found. <a href="/admin/blog/edit/new/">Create your first post</a>.</p></div>
            <?php else: ?>
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <a href="/admin/blog/edit/<?= $post['id'] ?>/"><?= htmlspecialchars($post['title']) ?></a>
                                <small class="text-muted">/blog/<?= htmlspecialchars($post['slug']) ?>/</small>
                            </td>
                            <td><span class="badge badge--<?= $post['status'] ?>"><?= ucfirst($post['status']) ?></span></td>
                            <td><?= htmlspecialchars($post['author']) ?></td>
                            <td><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                            <td class="actions-cell">
                                <a href="/admin/blog/edit/<?= $post['id'] ?>/" class="btn btn--small btn--outline-dark">Edit</a>
                                <?php if ($post['status'] === 'published'): ?>
                                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" class="btn btn--small btn--outline-dark" target="_blank">View</a>
                                <?php endif; ?>
                                <form method="POST" action="/admin/blog/delete/" class="inline-form" onsubmit="return confirm('Delete this post?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <button type="submit" class="btn btn--small btn--danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="admin-pagination">
                <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <a href="/admin/blog/?page=<?= $p ?>&status=<?= urlencode($status) ?>"
                   class="admin-pagination__link <?= $p === $pagination['current'] ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
