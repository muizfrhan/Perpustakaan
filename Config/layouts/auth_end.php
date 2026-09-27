<?php
/**
 * Penutup shell autentikasi.
 *
 * Dipasangkan dengan Config/layouts/auth_start.php.
 * Menyertakan komponen JS (toggle password) dan tautan antar-portal.
 */
?>
      </form>
      </div><!-- /.rounded-3xl (kartu form) -->

      <!-- Tautan ke portal lain -->
      <?php if (!empty($portalLain)): ?>
        <div class="mt-7">
          <div class="flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200"></span>
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">atau masuk sebagai</span>
            <span class="h-px flex-1 bg-slate-200"></span>
          </div>

          <div class="mt-4 grid grid-cols-2 gap-2.5">
            <?php foreach ($portalLain as $p): ?>
              <a href="<?= htmlspecialchars($p['href']) ?>"
                 class="group flex items-center justify-center gap-2 rounded-xl border border-slate-200
                        bg-white px-3 py-2.5 text-sm font-semibold text-slate-600
                        transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900
                        hover:shadow-card">
                <?= htmlspecialchars($p['label']) ?>
                <svg class="h-3.5 w-3.5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500"
                     fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="<?= $urlPrefix ?? '../../' ?>Assets/js/pusaku.js" defer></script>

</body>

</html>
