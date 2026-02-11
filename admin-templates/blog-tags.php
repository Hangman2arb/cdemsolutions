            <div class="admin-title-bar">
                <h1 class="admin-title">Blog Tags</h1>
            </div>

            <div class="admin-grid">
                <!-- Create/edit tag -->
                <div class="admin-card">
                    <div class="admin-card__header"><h2>Add Tag</h2></div>
                    <form method="POST" action="/admin/blog/tags/save/">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="0">
                        <div class="form-group">
                            <label for="tagName">Tag Name</label>
                            <input type="text" id="tagName" name="name" required placeholder="e.g. AI, DevOps">
                        </div>
                        <button type="submit" class="btn btn--primary btn--small">Add Tag</button>
                    </form>
                </div>

                <!-- Tags list -->
                <div class="admin-card">
                    <div class="admin-card__header"><h2>All Tags</h2></div>
                    <?php if (empty($tags)): ?>
                    <p class="admin-card__empty">No tags yet.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?= htmlspecialchars($tag['name']) ?></td>
                            <td><code><?= htmlspecialchars($tag['slug']) ?></code></td>
                            <td class="actions-cell">
                                <form method="POST" action="/admin/blog/tags/delete/" class="inline-form" onsubmit="return confirm('Delete this tag?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $tag['id'] ?>">
                                    <button type="submit" class="btn btn--small btn--danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
