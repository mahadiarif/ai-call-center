<?php if (isset($component)) { $__componentOriginalb525200bfa976483b4eaa0b7685c6e24 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widget','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;
                        background:<?php echo e($this->activeCalls > 0 ? '#22c55e' : '#9ca3af'); ?>;
                        box-shadow:0 0 0 4px <?php echo e($this->activeCalls > 0 ? 'rgba(34,197,94,0.25)' : 'rgba(156,163,175,0.2)'); ?>;"></span>
                    <strong style="font-size:1.1rem;">📞 Live Call Monitor</strong>
                </div>
                <span style="font-size:0.75rem;color:#9ca3af;">🕐 <?php echo e($this->currentTime); ?> (BD Time)</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px;">
                <div style="text-align:center;padding:16px;border-radius:12px;
                    border:2px solid <?php echo e($this->activeCalls > 0 ? '#86efac' : '#e5e7eb'); ?>;
                    background:<?php echo e($this->activeCalls > 0 ? '#f0fdf4' : '#f9fafb'); ?>;">
                    <div style="font-size:2.5rem;font-weight:900;color:<?php echo e($this->activeCalls > 0 ? '#16a34a' : '#9ca3af'); ?>;"><?php echo e($this->activeCalls); ?></div>
                    <div style="font-size:0.75rem;margin-top:4px;color:<?php echo e($this->activeCalls > 0 ? '#15803d' : '#6b7280'); ?>;">🔴 এখন Live কল</div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:12px;border:2px solid #93c5fd;background:#eff6ff;">
                    <div style="font-size:2.5rem;font-weight:900;color:#1d4ed8;"><?php echo e($this->lastHour); ?></div>
                    <div style="font-size:0.75rem;color:#1e40af;margin-top:4px;">⏱ শেষ ১ ঘণ্টা</div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:12px;border:2px solid #c4b5fd;background:#faf5ff;">
                    <div style="font-size:2.5rem;font-weight:900;color:#7c3aed;"><?php echo e($this->todayTotal); ?></div>
                    <div style="font-size:0.75rem;color:#6d28d9;margin-top:4px;">📅 আজকের মোট কল</div>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->recentCalls && $this->recentCalls->count() > 0): ?>
            <p style="font-size:0.85rem;font-weight:600;color:#374151;margin-bottom:8px;">🕑 সাম্প্রতিক কলসমূহ</p>
            <div style="overflow-x:auto;border-radius:8px;border:1px solid #e5e7eb;">
                <table style="width:100%;border-collapse:collapse;font-size:0.85rem;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">কাস্টমার</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">মোবাইল</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">IVR সার্ভিস</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">স্ট্যাটাস</th>
                            <th style="padding:8px 12px;text-align:left;font-size:0.75rem;color:#6b7280;">সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->recentCalls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $call): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isRecent = $call->created_at->diffInMinutes(now()) < 5;
                            $rowBg = $isRecent ? '#f0fdf4' : ($i % 2 === 0 ? '#ffffff' : '#f9fafb');
                            $statusStyle = match($call->status) {
                                'Pending'  => 'background:#fef9c3;color:#854d0e;',
                                'Resolved' => 'background:#dcfce7;color:#166534;',
                                'Rejected' => 'background:#fee2e2;color:#991b1b;',
                                default    => 'background:#f3f4f6;color:#374151;',
                            };
                        ?>
                        <tr style="background:<?php echo e($rowBg); ?>;border-top:1px solid #f3f4f6;">
                            <td style="padding:8px 12px;">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isRecent): ?><span style="display:inline-block;width:8px;height:8px;background:#22c55e;border-radius:50%;margin-right:6px;"></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php echo e($call->customer_name ?? 'অজানা'); ?>

                            </td>
                            <td style="padding:8px 12px;color:#6b7280;"><?php echo e($call->mobile_number ?? 'N/A'); ?></td>
                            <td style="padding:8px 12px;color:#6b7280;"><?php echo e($call->ivrService?->service_name ?? 'সাধারণ'); ?></td>
                            <td style="padding:8px 12px;">
                                <span style="padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:600;<?php echo e($statusStyle); ?>"><?php echo e($call->status); ?></span>
                            </td>
                            <td style="padding:8px 12px;font-size:0.75rem;color:#9ca3af;"><?php echo e($call->created_at->diffForHumans()); ?></td>
                        </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:24px;color:#9ca3af;">
                <div style="font-size:2.5rem;margin-bottom:8px;">📵</div>
                <p>এখন কোনো কল নেই</p>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $attributes = $__attributesOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__attributesOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24)): ?>
<?php $component = $__componentOriginalb525200bfa976483b4eaa0b7685c6e24; ?>
<?php unset($__componentOriginalb525200bfa976483b4eaa0b7685c6e24); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\ai-call-center\resources\views/filament/widgets/live-calls-widget.blade.php ENDPATH**/ ?>