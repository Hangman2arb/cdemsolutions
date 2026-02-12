            <div class="admin-title-bar">
                <h1 class="admin-title">Translate to Spanish</h1>
                <a href="/admin/blog/" class="btn btn--small btn--outline-dark">&larr; Back to Posts</a>
            </div>

            <form method="POST" action="/admin/blog/translate/<?= $post['id'] ?>/" class="blog-editor">
                <?= csrf_field() ?>
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="hidden" name="lang" value="es">

                <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- Left: English reference (read-only) -->
                    <div>
                        <div class="admin-card">
                            <div class="admin-card__header"><h3>English (Reference)</h3></div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" value="<?= htmlspecialchars($post['title']) ?>" disabled class="input--large">
                            </div>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" value="/blog/<?= htmlspecialchars($post['slug']) ?>/" disabled>
                            </div>
                            <div class="form-group">
                                <label>Excerpt</label>
                                <textarea rows="2" disabled><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Title</label>
                                <input type="text" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Meta Description</label>
                                <textarea rows="2" disabled><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Meta Keywords</label>
                                <input type="text" value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div class="admin-card" style="margin-top:1rem;">
                            <div class="admin-card__header"><h3>English Content (Reference)</h3></div>
                            <div class="form-group">
                                <textarea rows="15" disabled class="code-editor"><?= htmlspecialchars($post['content_html'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Spanish fields (editable) -->
                    <div>
                        <div class="admin-card">
                            <div class="admin-card__header"><h3>Spanish (Translation)</h3></div>
                            <div class="form-group">
                                <label for="title">Title *</label>
                                <input type="text" id="title" name="title"
                                       value="<?= htmlspecialchars($translation['title'] ?? '') ?>"
                                       placeholder="Translated title..." required class="input--large">
                            </div>
                            <div class="form-group">
                                <label for="slug">Slug</label>
                                <div class="input-prefix">
                                    <span>/blog/</span>
                                    <input type="text" id="slug" name="slug"
                                           value="<?= htmlspecialchars($translation['slug'] ?? '') ?>"
                                           placeholder="auto-generated-from-title">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="excerpt">Excerpt</label>
                                <textarea id="excerpt" name="excerpt" rows="2" placeholder="Resumen breve..."><?= htmlspecialchars($translation['excerpt'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title"
                                       value="<?= htmlspecialchars($translation['meta_title'] ?? '') ?>"
                                       placeholder="SEO title in Spanish...">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" rows="2"
                                          placeholder="SEO description in Spanish..."><?= htmlspecialchars($translation['meta_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="meta_keywords">Meta Keywords</label>
                                <input type="text" id="meta_keywords" name="meta_keywords"
                                       value="<?= htmlspecialchars($translation['meta_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2">
                            </div>
                        </div>
                        <div class="admin-card" style="margin-top:1rem;">
                            <div class="admin-card__header"><h3>Spanish Content (HTML)</h3></div>
                            <div class="form-group">
                                <textarea id="content_html" name="content_html" rows="15" class="code-editor"
                                          placeholder="Translated HTML content..."><?= htmlspecialchars($translation['content_html'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div style="margin-top:1rem; display:flex; gap:0.5rem;">
                            <button type="submit" class="btn btn--primary"><?= $translation ? 'Update Translation' : 'Save Translation' ?></button>
                            <?php if ($translation): ?>
                            <button type="submit" name="delete" value="1" class="btn btn--danger"
                                    onclick="return confirm('Delete this translation?')">Delete Translation</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
