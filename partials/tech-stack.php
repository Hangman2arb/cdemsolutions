    <!-- ===== TECH STACK / TECHNOLOGIES ===== -->
    <section class="tech-stack">
        <p class="tech-stack__label"><?= t('tech_stack.label') ?></p>
        <div class="tech-stack__track">
            <?php
            $techs = ['Python', 'React', 'AWS', 'Docker', 'Node.js', 'PostgreSQL', 'TypeScript', 'Kubernetes', 'TensorFlow', 'Git', 'Terraform'];
            for ($loop = 0; $loop < 2; $loop++):
                foreach ($techs as $tech):
            ?>
            <div class="tech-stack__item" title="<?= $tech ?>">
                <span><?= $tech ?></span>
            </div>
            <?php
                endforeach;
            endfor;
            ?>
        </div>
    </section>
