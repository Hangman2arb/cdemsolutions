            <div class="admin-title-bar">
                <h1 class="admin-title">Lead #<?= $lead['id'] ?></h1>
                <a href="/admin/leads/" class="btn btn--small btn--outline-dark">&larr; Back to Leads</a>
            </div>

            <div class="admin-grid">
                <div class="admin-card">
                    <div class="admin-card__header"><h2>Details</h2></div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name</label>
                            <span><?= htmlspecialchars($lead['name']) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email</label>
                            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a>
                        </div>
                        <div class="detail-item">
                            <label>Subject</label>
                            <span><?= htmlspecialchars($lead['subject']) ?></span>
                        </div>
                        <div class="detail-item detail-item--full">
                            <label>Message</label>
                            <div class="detail-message"><?= nl2br(htmlspecialchars($lead['message'])) ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Status</label>
                            <form method="POST" action="/admin/leads/status/" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                                <input type="hidden" name="redirect" value="/admin/leads/<?= $lead['id'] ?>/">
                                <select name="status" onchange="this.form.submit()" class="status-select status-select--<?= $lead['status'] ?>">
                                    <?php foreach (['new', 'contacted', 'qualified', 'closed', 'archived'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $lead['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="detail-item">
                            <label>Received</label>
                            <span><?= date('M j, Y \a\t H:i', strtotime($lead['created_at'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>IP Address</label>
                            <span><?= htmlspecialchars($lead['ip_address'] ?? 'N/A') ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email Sent</label>
                            <span><?= $lead['email_sent'] ? 'Yes' : 'No' ?></span>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card__header"><h2>Notes</h2></div>
                    <form method="POST" action="/admin/leads/notes/">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                        <div class="form-group">
                            <textarea name="notes" rows="6" placeholder="Add notes about this lead..."><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary btn--small">Save Notes</button>
                    </form>

                    <hr class="admin-divider">

                    <form method="POST" action="/admin/leads/delete/" onsubmit="return confirm('Delete this lead permanently?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $lead['id'] ?>">
                        <button type="submit" class="btn btn--small btn--danger">Delete Lead</button>
                    </form>
                </div>
            </div>
