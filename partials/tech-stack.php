    <!-- ===== TECH STACK / TECHNOLOGIES ===== -->
    <section class="tech-stack">
        <p class="tech-stack__label"><?= t('tech_stack.label') ?></p>
        <div class="tech-stack__track">
            <?php
            $techs = [
                ['name' => 'Python', 'icon' => 'python'],
                ['name' => 'React', 'icon' => 'react'],
                ['name' => 'AWS', 'icon' => 'aws'],
                ['name' => 'Docker', 'icon' => 'docker'],
                ['name' => 'Node.js', 'icon' => 'nodejs'],
                ['name' => 'PostgreSQL', 'icon' => 'postgresql'],
                ['name' => 'TypeScript', 'icon' => 'typescript'],
                ['name' => 'Kubernetes', 'icon' => 'kubernetes'],
                ['name' => 'TensorFlow', 'icon' => 'tensorflow'],
                ['name' => 'Git', 'icon' => 'git'],
                ['name' => 'Terraform', 'icon' => 'terraform'],
            ];
            for ($loop = 0; $loop < 2; $loop++):
                foreach ($techs as $tech):
            ?>
            <div class="tech-stack__item" title="<?= $tech['name'] ?>">
                <img src="/img/tech/<?= $tech['icon'] ?>.svg" alt="<?= $tech['name'] ?>" width="40" height="40" loading="lazy">
                <span><?= $tech['name'] ?></span>
            </div>
            <?php
                endforeach;
            endfor;
            ?>
        </div>
    </section>
