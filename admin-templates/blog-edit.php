            <div class="admin-title-bar">
                <h1 class="admin-title"><?= $post['id'] ? 'Edit Post' : 'New Post' ?></h1>
                <a href="/admin/blog/" class="btn btn--small btn--outline-dark">&larr; Back to Posts</a>
            </div>

            <form method="POST" action="/admin/blog/save/" class="blog-editor">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $post['id'] ?>">

                <div class="admin-grid admin-grid--editor">
                    <!-- Main column -->
                    <div class="admin-card">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>"
                                   placeholder="Post title..." required class="input--large">
                        </div>

                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <div class="input-prefix">
                                <span>/blog/</span>
                                <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($post['slug']) ?>"
                                       placeholder="auto-generated-from-title">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="excerpt">Excerpt</label>
                            <textarea id="excerpt" name="excerpt" rows="2" placeholder="Brief summary..."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="content_html">Content (HTML)</label>
                            <textarea id="content_html" name="content_html" rows="20" class="code-editor"
                                      placeholder="Write your post content in HTML..."><?= htmlspecialchars($post['content_html'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Sidebar column -->
                    <div class="editor-sidebar">
                        <div class="admin-card">
                            <div class="admin-card__header"><h3>Publish</h3></div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="author">Author</label>
                                <input type="text" id="author" name="author" value="<?= htmlspecialchars($post['author'] ?? 'CDEM Solutions') ?>">
                            </div>
                            <button type="submit" class="btn btn--primary btn--full"><?= $post['id'] ? 'Update Post' : 'Create Post' ?></button>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card__header"><h3>Featured Image</h3></div>
                            <div class="form-group">
                                <input type="text" id="featured_image" name="featured_image"
                                       value="<?= htmlspecialchars($post['featured_image'] ?? '') ?>"
                                       placeholder="/img/blog/image.jpg">
                                <div class="image-upload-area" id="imageUploadArea">
                                    <input type="file" id="imageUpload" accept="image/*" class="sr-only">
                                    <label for="imageUpload" class="btn btn--small btn--outline-dark" style="margin-top:8px">Upload Image</label>
                                </div>
                                <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="image-preview" alt="Preview">
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card__header"><h3>Tags</h3></div>
                            <div class="form-group">
                                <input type="text" id="tags" name="tags"
                                       value="<?= htmlspecialchars($post['tags'] ?? '') ?>"
                                       placeholder="tag1, tag2, tag3">
                                <?php if (!empty($allTags)): ?>
                                <div class="tag-suggestions">
                                    <?php foreach ($allTags as $tag): ?>
                                    <button type="button" class="tag-suggestion" data-tag="<?= htmlspecialchars($tag['name']) ?>"><?= htmlspecialchars($tag['name']) ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="admin-card__header"><h3>SEO</h3></div>
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" id="meta_title" name="meta_title"
                                       value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>"
                                       placeholder="SEO title...">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea id="meta_description" name="meta_description" rows="2"
                                          placeholder="SEO description..."><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="meta_keywords">Meta Keywords</label>
                                <input type="text" id="meta_keywords" name="meta_keywords"
                                       value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
