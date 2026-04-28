<div style="padding:0.5rem 0;">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php
            $styles = [
                'danger'  => 'background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;',
                'warning' => 'background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e;',
                'success' => 'background:#f0fdf4;border-left:4px solid #22c55e;color:#166534;',
            ];
            $s = $styles[$alert['type']] ?? $styles['success'];
        ?>
        <div style="<?php echo e($s); ?> display:flex;align-items:center;justify-content:space-between;border-radius:6px;padding:0.6rem 1rem;margin-bottom:0.4rem;">
            <span style="font-size:0.875rem;font-weight:500;">
                <?php echo e($alert['icon']); ?> <?php echo e($alert['message']); ?>

            </span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($alert['link']): ?>
                <a href="<?php echo e($alert['link']); ?>" style="font-size:0.75rem;font-weight:600;text-decoration:underline;white-space:nowrap;margin-left:1rem;color:inherit;">
                    দেখুন →
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\ai-call-center\resources\views/filament/widgets/smart-notification-widget.blade.php ENDPATH**/ ?>