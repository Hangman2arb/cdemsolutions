    <!-- ===== TECH STACK / TECHNOLOGIES ===== -->
    <section class="tech-stack">
        <p class="tech-stack__label"><?= t('tech_stack.label') ?></p>
        <div class="tech-stack__track">
            <?php
            $techs = [
                ['name' => 'Python', 'icon' => 'python'],
                ['name' => 'React', 'icon' => 'react'],
                ['name' => 'Next.js', 'icon' => 'nextjs'],
                ['name' => 'Vue.js', 'icon' => 'vuejs'],
                ['name' => 'Angular', 'icon' => 'angular'],
                ['name' => 'TypeScript', 'icon' => 'typescript'],
                ['name' => 'Node.js', 'icon' => 'nodejs'],
                ['name' => 'Java', 'icon' => 'java'],
                ['name' => 'Go', 'icon' => 'go'],
                ['name' => 'Ruby', 'icon' => 'ruby'],
                ['name' => 'Rails', 'icon' => 'rails'],
                ['name' => 'PHP', 'icon' => 'php'],
                ['name' => 'Swift', 'icon' => 'swift'],
                ['name' => 'Rust', 'icon' => 'rust'],
                ['name' => 'Flutter', 'icon' => 'flutter'],
                ['name' => 'AWS', 'icon' => 'aws'],
                ['name' => 'Docker', 'icon' => 'docker'],
                ['name' => 'Kubernetes', 'icon' => 'kubernetes'],
                ['name' => 'Terraform', 'icon' => 'terraform'],
                ['name' => 'PostgreSQL', 'icon' => 'postgresql'],
                ['name' => 'MongoDB', 'icon' => 'mongodb'],
                ['name' => 'MySQL', 'icon' => 'mysql'],
                ['name' => 'Redis', 'icon' => 'redis'],
                ['name' => 'GraphQL', 'icon' => 'graphql'],
                ['name' => 'TensorFlow', 'icon' => 'tensorflow'],
                ['name' => 'Git', 'icon' => 'git'],
                ['name' => 'Linux', 'icon' => 'linux'],
                ['name' => 'Sass', 'icon' => 'sass'],
                ['name' => 'Figma', 'icon' => 'figma'],
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
