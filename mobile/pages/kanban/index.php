<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../bootstrap.php';
global $db;

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$tenant_id = $_SESSION['tenant_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Retrieve filter
$filter = $_GET['filter'] ?? 'all'; // all, my, assigned

// Fetch tasks using Task model
$taskModel = new Task();
$tasks = $taskModel->getTasksForTenant($tenant_id, $filter, $user_id);

// Fetch dynamic boards
$boards = $taskModel->getBoardsForTenant($tenant_id);

// Fetch institutional users
$userModel = new User();
$users = $userModel->whereWithCreator('tenant_id', $tenant_id);

$colorMap = ['bg-indigo-500 text-indigo-50', 'bg-emerald-500 text-emerald-50', 'bg-amber-500 text-amber-50', 'bg-rose-500 text-rose-50', 'bg-teal-500 text-teal-50', 'bg-blue-500 text-blue-50', 'bg-purple-500 text-purple-50'];
?>

<div class="space-y-4.5 animate-fade-in pb-16">
    
    <!-- 1. Header Toolbar (Collaborators Stack & Top Actions in a Single Row) -->
    <div class="flex items-center justify-between bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2.5 rounded-2xl shadow-sm">
        <!-- Collaborator Avatar Overlapping Pile -->
        <div class="flex items-center gap-1.5">
            <div class="flex -space-x-2 overflow-hidden items-center">
                <?php 
                $limit = 3;
                $count = 0;
                foreach ($users as $u):
                    if ($count >= $limit) break;
                    $parts = preg_split('/\s+/', trim($u['name']));
                    $init = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? 'S', 0, 1));
                    $cIdx = abs(crc32($u['name'])) % count($colorMap);
                    $cClass = $colorMap[$cIdx];
                    $count++;
                ?>
                    <div class="inline-flex size-7.5 items-center justify-center rounded-full <?= $cClass ?> border-2 border-white dark:border-zinc-950 text-[9px] font-black uppercase shrink-0 shadow-sm">
                        <?= $init ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($users) > $limit): ?>
                    <div class="inline-flex size-7.5 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-[9px] font-black text-zinc-500 dark:text-zinc-400 border-2 border-white dark:border-zinc-950 shrink-0">
                        +<?= count($users) - $limit ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Add Member Trigger -->
            <button onclick="switchTab('settings')" class="size-7.5 rounded-full border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-950 text-zinc-500 dark:text-zinc-400 flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </button>
        </div>

        <!-- Premium Action Buttons -->
        <div class="flex items-center gap-2">
            <!-- Search Button -->
            <button onclick="toggleMobileSearch()" class="size-9.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-950/40 text-zinc-650 dark:text-zinc-450 flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            
            <!-- Filters Toggle Button -->
            <button onclick="openSheet('mobile-filter-sheet')" class="size-9.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-950/40 text-zinc-650 dark:text-zinc-450 flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            </button>
            
            <!-- Add Board Column Button -->
            <button onclick="openMobileBoardAddSheet()" class="size-9.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-950/40 text-zinc-650 dark:text-zinc-450 flex items-center justify-center active:scale-95 transition-all cursor-pointer" title="Yeni Sütun Ekle">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18M15 3v18M12 9v6M9 12h6"/></svg>
            </button>
            
            <!-- Add Task Button -->
            <button onclick="openMobileEkleSheet(0)" class="size-9.5 rounded-xl bg-zinc-950 dark:bg-white text-white dark:text-zinc-950 flex items-center justify-center shadow active:scale-95 transition-all cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
            </button>
        </div>
    </div>

    <!-- 3. Collapsible Search Input Row -->
    <div id="mobile-search-row" class="hidden animate-fade-in">
        <div class="relative flex items-center bg-zinc-100 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-850 rounded-xl px-3 py-2 gap-2 w-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="text-zinc-400 shrink-0" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="kanban-search-input" class="bg-transparent border-0 outline-none text-xs w-full font-bold text-zinc-800 dark:text-zinc-200" placeholder="Görev başlığı veya detaylarında ara..." oninput="filterKanbanCards()">
        </div>
    </div>

<style>
/* Horizontal swiping layout for columns */
#mobile-tasks-container {
    display: flex !important;
    flex-direction: row !important;
    overflow-x: auto !important;
    scroll-snap-type: x mandatory !important;
    -webkit-overflow-scrolling: touch !important;
    scroll-behavior: smooth !important;
    gap: 16px !important;
    padding-bottom: 24px !important;
}

#mobile-tasks-container::-webkit-scrollbar {
    display: none !important;
}

.mobile-status-list {
    flex: 0 0 calc(100vw - 32px) !important;
    width: calc(100vw - 32px) !important;
    scroll-snap-align: start !important;
    display: block !important;
}
</style>

    <!-- 5. Column Views & Premium Kanban Cards (Identical to the Screenshot Design) -->
    <div class="relative w-full overflow-visible">
        <!-- Horizontal scroll container -->
        <div id="mobile-tasks-container" class="space-y-4" onscroll="updateMobileKanbanNavButtons()">
        <?php foreach ($boards as $index => $board):
            $boardId = intval($board['id']);
            $colTasks = array_filter($tasks, fn($t) => intval($t['board_id'] ?? 0) === $boardId);
            
            // Find next board in sorting list
            $nextBoardId = null;
            $nextBoardTitle = "";
            for ($i = 0; $i < count($boards); $i++) {
                if (intval($boards[$i]['id']) === $boardId && isset($boards[$i + 1])) {
                    $nextBoardId = intval($boards[$i + 1]['id']);
                    $nextBoardTitle = $boards[$i + 1]['title'];
                    break;
                }
            }
            
            $colHeaderTitle = htmlspecialchars($board['title']);
            if (strtolower($colHeaderTitle) === 'backlog') $colHeaderTitle = 'Backlog';
            elseif (strtolower($colHeaderTitle) === 'in progress') $colHeaderTitle = 'In Progress';
            elseif (strtolower($colHeaderTitle) === 'done') $colHeaderTitle = 'Done';
        ?>
            <!-- Status Card Column -->
            <div id="col-list-<?= $boardId ?>" class="mobile-status-list space-y-3.5 rounded-2xl bg-zinc-50 dark:bg-zinc-950/20 border border-zinc-200/50 dark:border-zinc-900/50 p-3.5">
                
                <!-- Column Sub-Header with drag and add icons matching screenshot -->
                <div class="flex items-center justify-between pb-1.5 px-0.5">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-extrabold text-zinc-800 dark:text-zinc-100"><?= $colHeaderTitle ?></span>
                        <span class="inline-flex size-5 items-center justify-center rounded-full bg-zinc-200/60 dark:bg-zinc-900 text-[10px] font-black text-zinc-500 dark:text-zinc-400"><?= count($colTasks) ?></span>
                    </div>
                    
                    <div class="flex items-center gap-1.5 text-zinc-450 dark:text-zinc-550">
                        <button onclick="openMobileBoardOptionsSheet(<?= $boardId ?>, '<?= htmlspecialchars($board['title'], ENT_QUOTES) ?>')" class="hover:text-zinc-800 dark:hover:text-white transition-all cursor-pointer size-5 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" class="opacity-60" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </button>
                        <button onclick="openMobileEkleSheet(<?= $boardId ?>)" class="hover:text-zinc-800 dark:hover:text-white transition-all cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
                        </button>
                    </div>
                </div>

                <?php if (empty($colTasks)): ?>
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-250/10 dark:border-zinc-850 p-8 rounded-xl text-center space-y-2.5 shadow-sm">
                        <p class="text-xs font-bold text-zinc-400 dark:text-zinc-500">Aşama sütununda henüz görev yok.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($colTasks as $task): 
                        $isOverdue = false;
                        if (!empty($task['due_date'])) {
                            $isOverdue = strtotime($task['due_date']) < strtotime(date('Y-m-d'));
                        }
                    ?>
                        <!-- Swipeable Premium Mobile Task Card -->
                        <div class="swipe-container relative overflow-hidden rounded-2xl border border-zinc-200/50 dark:border-zinc-850 shadow-sm bg-white dark:bg-zinc-900 mobile-task-card"
                             data-id="<?= $task['id'] ?>"
                             data-title="<?= htmlspecialchars($task['title']) ?>"
                             data-desc="<?= htmlspecialchars($task['description']) ?>"
                             data-priority="<?= $task['priority'] ?>"
                             data-board-id="<?= $boardId ?>"
                             data-date="<?= $task['due_date'] ?>"
                             data-assignees="<?= htmlspecialchars(json_encode(array_column($task['assignees'], 'id'))) ?>">
                             
                             <!-- Left Swipe action: Move to Next Board Column -->
                             <div class="swipe-left-actions absolute inset-y-0 left-0 flex items-stretch gap-4 pl-4 z-0">
                                <?php if ($nextBoardId !== null): ?>
                                    <button onclick="event.stopPropagation(); updateMobileTaskStatus(<?= $task['id'] ?>, <?= $nextBoardId ?>)" class="w-16 h-full bg-transparent text-emerald-500 flex flex-col items-center justify-center transition-all cursor-pointer gap-1 hover:scale-105 active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
                                        <span class="text-[8px] font-bold uppercase tracking-wider leading-none truncate max-w-[60px]"><?= htmlspecialchars($nextBoardTitle) ?></span>
                                    </button>
                                <?php else: ?>
                                    <span class="self-center text-[8px] text-zinc-400 italic pl-2">Son Aşama</span>
                                <?php endif; ?>
                             </div>

                             <!-- Right Swipe action: Delete Card -->
                             <div class="swipe-right-actions absolute inset-y-0 right-0 flex items-stretch z-0">
                                 <button onclick="event.stopPropagation(); deleteMobileTask(<?= $task['id'] ?>)" class="w-14 h-full bg-transparent text-rose-500 flex flex-col items-center justify-center transition-all cursor-pointer gap-1 hover:scale-105 active:scale-95">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                     <span class="text-[8px] font-bold uppercase tracking-wider leading-none">Sil</span>
                                 </button>
                             </div>

                             <!-- Front Layer Content - Exact Screenshot Replica -->
                             <div class="swipe-front bg-white dark:bg-zinc-900 p-4.5 space-y-4.5 transition-all active:bg-zinc-50/50 dark:active:bg-zinc-800/30 cursor-pointer"
                                  onclick="openMobileEditSheet(this.parentElement)">
                                  
                                  <!-- Task Head Title & Description -->
                                  <div class="space-y-1.5">
                                      <h4 class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100 leading-snug tracking-tight"><?= htmlspecialchars($task['title']) ?></h4>
                                      <?php if (!empty($task['description'])): ?>
                                          <p class="text-[11px] font-semibold text-zinc-450 dark:text-zinc-400 leading-relaxed line-clamp-2"><?= htmlspecialchars($task['description']) ?></p>
                                      <?php endif; ?>
                                  </div>

                                  <!-- Overlapping Avatars & Circular Custom Progress Pill (Screenshot Style) -->
                                  <div class="flex items-center justify-between">
                                      
                                      <!-- Overlapping Collaborator Avatars -->
                                      <div class="flex -space-x-1.5 overflow-hidden items-center">
                                          <?php 
                                          foreach ($task['assignees'] as $asn): 
                                              $parts = preg_split('/\s+/', trim($asn['name']));
                                              $init = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? 'S', 0, 1));
                                              $cIdx = abs(crc32($asn['name'])) % count($colorMap);
                                              $cClass = $colorMap[$cIdx];
                                          ?>
                                              <div class="inline-flex size-6.5 items-center justify-center rounded-full <?= $cClass ?> border-2 border-white dark:border-zinc-900 text-[8px] font-black uppercase shrink-0 shadow-sm">
                                                  <?= $init ?>
                                              </div>
                                          <?php endforeach; ?>
                                          <?php if (empty($task['assignees'])): ?>
                                              <span class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-550 italic">Atanmamış</span>
                                          <?php endif; ?>
                                      </div>

                                      <!-- Premium Circular Progress Indicator Pill -->
                                      <div class="flex items-center gap-1.5">
                                          <?php 
                                          $mockPercent = 0;
                                          if ($board['title'] === 'Done' || stripos($board['title'], 'tamam') !== false) {
                                              $mockPercent = 100;
                                          } elseif ($board['title'] === 'In Progress' || stripos($board['title'], 'yapılıyor') !== false) {
                                              $mockPercent = 50;
                                          } else {
                                              $mockPercent = 10; // Default mockup state
                                          }
                                          ?>
                                          <?php if ($mockPercent === 100): ?>
                                              <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-50/70 dark:bg-emerald-950/20 px-2.5 py-1 rounded-full border border-emerald-100/50 dark:border-emerald-900/30">
                                                  <span class="size-2 rounded-full bg-emerald-500"></span>
                                                  100%
                                              </span>
                                          <?php elseif ($mockPercent === 50): ?>
                                              <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-amber-600 dark:text-amber-400 bg-amber-50/70 dark:bg-amber-950/20 px-2.5 py-1 rounded-full border border-amber-100/50 dark:border-amber-900/30">
                                                  <span class="size-2 rounded-full border-2 border-amber-500 bg-transparent animate-pulse"></span>
                                                  50%
                                              </span>
                                          <?php else: ?>
                                              <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800 px-2.5 py-1 rounded-full border border-zinc-200 dark:border-zinc-700">
                                                  <span class="size-2 rounded-full border border-zinc-450 bg-transparent"></span>
                                                  10%
                                              </span>
                                          <?php endif; ?>
                                      </div>
                                  </div>

                                  <!-- Card Bottom Divider -->
                                  <div class="border-t border-zinc-100 dark:border-zinc-800/60 pt-2.5 flex items-center justify-between">
                                      <div class="flex items-center gap-2">
                                          <!-- Priority Tags -->
                                          <?php if ($task['priority'] === 'high'): ?>
                                              <span class="text-[9px] font-extrabold tracking-wider uppercase px-2.5 py-0.5 rounded-lg bg-rose-50 text-rose-600 border border-rose-100/60 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30">High</span>
                                          <?php elseif ($task['priority'] === 'medium'): ?>
                                               <span class="text-[9px] font-extrabold tracking-wider uppercase px-2.5 py-0.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-300 border border-zinc-200/50 dark:border-zinc-700/50">Medium</span>
                                          <?php else: ?>
                                               <span class="text-[9px] font-extrabold tracking-wider uppercase px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-600 border border-blue-100/60 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900/30">Low</span>
                                          <?php endif; ?>

                                          <!-- Compact Due Date Badge -->
                                          <div class="flex items-center gap-1 text-[9px] font-bold <?= $isOverdue ? 'text-rose-600 bg-rose-50 border border-rose-100/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30 px-2 py-0.5 rounded-lg animate-pulse' : 'text-zinc-500 bg-zinc-50 border border-zinc-150 dark:bg-zinc-850 dark:text-zinc-400 dark:border-zinc-800 px-2 py-0.5 rounded-lg' ?>">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                              <span><?= !empty($task['due_date']) ? date('d.m.Y', strtotime($task['due_date'])) : 'Süresiz' ?></span>
                                          </div>
                                      </div>

                                      <!-- Attachments and Comment Count (Screenshot Style) -->
                                      <div class="flex items-center gap-2 text-[10px] font-bold text-zinc-400 dark:text-zinc-550">
                                          <span class="inline-flex items-center gap-0.5">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                              <?= abs(crc32($task['title'])) % 3 + 1 ?>
                                          </span>
                                          <span class="inline-flex items-center gap-0.5">
                                              <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                              <?= abs(crc32($task['title'])) % 4 + 2 ?>
                                          </span>
                                      </div>
                                  </div>
                              </div>
                         </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- Left navigation button -->
        <button id="mobile-kanban-prev-btn" onclick="scrollMobileKanban('left')" class="absolute left-[-12px] top-1/2 -translate-y-1/2 z-30 size-9 rounded-full flex items-center justify-center backdrop-blur-md bg-white/90 dark:bg-zinc-900/90 border border-zinc-200/50 dark:border-zinc-800/50 shadow-md text-zinc-900 dark:text-zinc-100 active:scale-90 transition-all opacity-0 pointer-events-none cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>

        <!-- Right navigation button -->
        <button id="mobile-kanban-next-btn" onclick="scrollMobileKanban('right')" class="absolute right-[-12px] top-1/2 -translate-y-1/2 z-30 size-9 rounded-full flex items-center justify-center backdrop-blur-md bg-white/90 dark:bg-zinc-900/90 border border-zinc-200/50 dark:border-zinc-800/50 shadow-md text-zinc-900 dark:text-zinc-100 active:scale-90 transition-all cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>
    </div>

<!-- 6. COLLAPSIBLE FILTER BOTTOM SHEET (Native iOS/Android App Look) -->
<div id="mobile-filter-sheet" class="bottom-sheet flex flex-col max-h-[40%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="px-6 pb-8 pt-2 flex-1 flex flex-col justify-between space-y-4">
        <div class="space-y-1.5">
            <h3 class="text-sm font-extrabold text-zinc-900 dark:text-zinc-50">Görev Filtreleri</h3>
            <p class="text-xs text-zinc-400 font-semibold">Gösterilecek görev kapsamını seçin:</p>
        </div>
        <div class="flex flex-col gap-2">
            <button onclick="switchMobileFilter('all'); closeAllSheets();" class="w-full py-3 rounded-xl text-xs font-bold text-left px-4 flex items-center justify-between transition-all <?= $filter === 'all' ? 'bg-zinc-100 dark:bg-zinc-800/60 text-zinc-950 dark:text-white font-extrabold' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800/30' ?>">
                <span>Tüm Kurum Görevleri</span>
                <?php if ($filter === 'all'): ?><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
            </button>
            <button onclick="switchMobileFilter('my'); closeAllSheets();" class="w-full py-3 rounded-xl text-xs font-bold text-left px-4 flex items-center justify-between transition-all <?= $filter === 'my' ? 'bg-zinc-100 dark:bg-zinc-800/60 text-zinc-950 dark:text-white font-extrabold' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800/30' ?>">
                <span>Kendi Oluşturduğum Görevler</span>
                <?php if ($filter === 'my'): ?><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
            </button>
            <button onclick="switchMobileFilter('assigned'); closeAllSheets();" class="w-full py-3 rounded-xl text-xs font-bold text-left px-4 flex items-center justify-between transition-all <?= $filter === 'assigned' ? 'bg-zinc-100 dark:bg-zinc-800/60 text-zinc-950 dark:text-white font-extrabold' : 'text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800/30' ?>">
                <span>Bana Atanan Görevler</span>
                <?php if ($filter === 'assigned'): ?><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
            </button>
        </div>
    </div>
</div>

<!-- 7. MOBILE TASK CREATION & EDITING BOTTOM SHEET -->
<div id="mobile-task-sheet" class="bottom-sheet flex flex-col max-h-[85%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    
    <div class="overflow-y-auto app-scroll px-6 pb-36 flex-1 space-y-5">
        <h3 id="mobile-sheet-title" class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Yeni Görev Ekle</h3>
        
        <form id="mobileTaskForm" class="space-y-4">
            <input type="hidden" name="id" id="mobile-form-id" value="">
            
            <!-- Title -->
            <div class="space-y-1.5">
                <label for="mobile-form-title">Görev Başlığı <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="mobile-form-title" required class="mobile-input" placeholder="Yapılacak iş tanımı...">
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
                <label for="mobile-form-desc">Detaylar / Açıklama</label>
                <textarea name="description" id="mobile-form-desc" rows="3" class="mobile-input py-2.5" placeholder="İş detaylarını açıklayın..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Priority -->
                <div class="space-y-1.5">
                    <label for="mobile-form-priority">Öncelik Seviyesi</label>
                    <select name="priority" id="mobile-form-priority" class="mobile-input">
                        <option value="low">Düşük</option>
                        <option value="medium" selected>Orta</option>
                        <option value="high">Yüksek</option>
                    </select>
                </div>

                <!-- Sütun (Board) Selector -->
                <div class="space-y-1.5">
                    <label for="mobile-form-board-id">Sütun (Board)</label>
                    <select name="board_id" id="mobile-form-board-id" class="mobile-input">
                        <?php foreach ($boards as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Son Tarih (Flatpickr) -->
            <div class="space-y-1.5">
                <label for="mobile-form-date">Son Tarih</label>
                <input type="text" name="due_date" id="mobile-form-date" class="mobile-input dark:[color-scheme:dark]" placeholder="Tarih seçilmedi">
            </div>

            <!-- Assignees Checklist -->
            <div class="space-y-1.5">
                <label>Kurum İçi Görevliler (Atananlar)</label>
                <div class="border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 max-h-40 overflow-y-auto space-y-2 bg-zinc-50/40 dark:bg-zinc-950/20 custom-scrollbar">
                    <?php if (empty($users)): ?>
                        <p class="text-[10px] text-zinc-400 italic text-center py-2">Kurumda atanabilecek başka üye yok.</p>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            $init = '';
                            $parts = preg_split('/\s+/', trim($u['name']));
                            $init = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? 'S', 0, 1));
                            $cIdx = abs(crc32($u['name'])) % count($colorMap);
                            $cClass = $colorMap[$cIdx];
                        ?>
                            <label class="label gap-3 cursor-pointer p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/40 active:bg-zinc-100/70 transition-all w-full" style="display: flex !important; align-items: center !important;">
                                <input type="checkbox" name="assignees[]" value="<?= $u['id'] ?>" class="input shrink-0">
                                <div class="inline-flex size-7 items-center justify-center rounded-full <?= $cClass ?> text-[10px] font-extrabold shrink-0 shadow-sm">
                                    <?= $init ?>
                                </div>
                                <div class="min-w-0 flex-1 ml-1">
                                    <span class="block truncate leading-none text-xs font-bold text-zinc-900 dark:text-zinc-100"><?= htmlspecialchars($u['name']) ?></span>
                                    <span class="block text-[10px] text-zinc-400 dark:text-zinc-500 font-medium truncate mt-1"><?= htmlspecialchars($u['email']) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3 mt-4">
                <button type="button" class="btn-outline flex-1 justify-center" onclick="closeAllSheets()">İptal</button>
                <button type="submit" class="btn flex-1 justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 8. MOBILE BOARD OPTIONS BOTTOM SHEET (Edit Title / Delete Board) -->
<div id="mobile-board-options-sheet" class="bottom-sheet flex flex-col max-h-[60%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="px-6 pb-12 pt-2 flex-1 flex flex-col justify-between space-y-5 overflow-y-auto">
        <div class="space-y-1.5">
            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Sütun Ayarları</h3>
            <p class="text-xs text-zinc-400 font-semibold">Sütun adını düzenleyebilir veya sütunu silebilirsiniz:</p>
        </div>
        
        <div class="space-y-4">
            <!-- Edit Title Form -->
            <div class="space-y-2">
                <label for="board-options-title-input" class="text-xs font-bold text-zinc-650 dark:text-zinc-350">Sütun Başlığı</label>
                <div class="flex gap-2">
                    <input type="text" id="board-options-title-input" class="mobile-input flex-1" placeholder="Sütun adı yazın...">
                    <button onclick="saveMobileBoardTitle()" class="btn py-2 px-4 h-auto text-xs font-black shadow cursor-pointer active:scale-95 transition-all">Kaydet</button>
                </div>
            </div>
            
            <div class="border-t border-zinc-150 dark:border-zinc-800/80 my-4"></div>
            
            <!-- Delete Section -->
            <div class="space-y-2">
                <label class="text-xs font-bold text-rose-500">Tehlikeli Bölge</label>
                <p class="text-[10px] font-bold text-zinc-400 leading-normal">Bu sütunu silmek, içindeki TÜM GÖREVLERİ kalıcı olarak silecektir. Bu işlem geri alınamaz.</p>
                <button onclick="deleteMobileBoard()" class="w-full btn bg-rose-600 hover:bg-rose-700 text-white dark:bg-rose-500/10 dark:text-rose-400 dark:border dark:border-rose-500/20 py-3 flex items-center justify-center gap-1.5 font-black text-xs cursor-pointer active:scale-95 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2m-9 5h10m-10 4h10"/></svg>
                    Sütunu ve Görevleri Kalıcı Olarak Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 9. MOBILE BOARD ADD BOTTOM SHEET -->
<div id="mobile-board-add-sheet" class="bottom-sheet flex flex-col max-h-[50%] bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
    <div class="w-12 h-1 bg-zinc-300 dark:bg-zinc-800 rounded-full mx-auto my-3 flex-shrink-0"></div>
    <div class="px-6 pb-12 pt-2 flex-1 flex flex-col justify-between space-y-5">
        <div class="space-y-1.5">
            <h3 class="text-base font-extrabold text-zinc-900 dark:text-zinc-50">Yeni Sütun Ekle</h3>
            <p class="text-xs text-zinc-400 font-semibold">İşlerinizi gruplandırabileceğiniz yeni bir aşama sütunu oluşturun:</p>
        </div>
        
        <form id="mobileBoardAddForm" class="space-y-4" onsubmit="saveMobileNewBoard(event)">
            <div class="space-y-2">
                <label for="board-add-title-input" class="text-xs font-bold text-zinc-650 dark:text-zinc-350">Sütun Başlığı <span class="text-rose-500">*</span></label>
                <input type="text" id="board-add-title-input" required class="mobile-input w-full" placeholder="Sütun adı (örneğin: İncelemede)">
            </div>
            
            <div class="flex gap-3 mt-4">
                <button type="button" class="btn-outline flex-1 justify-center py-3" onclick="closeSheet('mobile-board-add-sheet')">İptal</button>
                <button type="submit" class="btn flex-1 justify-center gap-1.5 cursor-pointer py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
                    Sütunu Ekle
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Kanban Mobile Interactive Script Block -->
<script>
// Board management state
let mobileActiveBoardId = null;
let mobileActiveBoardTitle = '';

// Open Board Options Bottom Sheet
function openMobileBoardOptionsSheet(boardId, boardTitle) {
    mobileActiveBoardId = boardId;
    mobileActiveBoardTitle = boardTitle;
    
    const titleInput = document.getElementById('board-options-title-input');
    if (titleInput) {
        titleInput.value = boardTitle;
    }
    
    openSheet('mobile-board-options-sheet');
}

// Save Board Title (Rename column)
function saveMobileBoardTitle() {
    const titleInput = document.getElementById('board-options-title-input');
    if (!titleInput || !mobileActiveBoardId) return;
    
    const newTitle = titleInput.value.trim();
    if (newTitle === '') {
        alert('Lütfen sütun başlığı girin.');
        return;
    }
    
    if (newTitle === mobileActiveBoardTitle) {
        closeSheet('mobile-board-options-sheet');
        return;
    }
    
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', mobileActiveBoardId);
    formData.append('t', newTitle);
    
    fetch(basePath + '/kanban-board-baslik-guncelle', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeSheet('mobile-board-options-sheet');
            if (typeof window.showToast === 'function') {
                window.showToast({ category: 'success', title: 'Başarılı', description: 'Sütun başlığı güncellendi.' });
            } else {
                alert('Sütun başlığı güncellendi.');
            }
            setTimeout(() => { window.location.reload(); }, 600);
        } else {
            alert(data.error || 'Sütun başlığı güncellenemedi.');
        }
    })
    .catch(err => {
        alert('Sunucuyla bağlantı kurulamadı.');
    });
}

// Delete Board (Delete column and tasks)
function deleteMobileBoard() {
    if (!mobileActiveBoardId) return;
    
    if (!confirm(`"${mobileActiveBoardTitle}" sütununu ve içindeki TÜM GÖREVLERİ silmek istediğinize emin misiniz?`)) return;
    
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', mobileActiveBoardId);
    
    fetch(basePath + '/kanban-board-sil', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeSheet('mobile-board-options-sheet');
            if (typeof window.showToast === 'function') {
                window.showToast({ category: 'success', title: 'Başarılı', description: 'Sütun silindi.' });
            } else {
                alert('Sütun silindi.');
            }
            setTimeout(() => { window.location.reload(); }, 600);
        } else {
            alert(data.error || 'Sütun silinemedi.');
        }
    })
    .catch(err => {
        alert('Sunucuyla bağlantı kurulamadı.');
    });
}

// Open Board Add Bottom Sheet
function openMobileBoardAddSheet() {
    const form = document.getElementById('mobileBoardAddForm');
    if (form) form.reset();
    openSheet('mobile-board-add-sheet');
}

// Save New Board (Create dynamic column)
function saveMobileNewBoard(event) {
    event.preventDefault();
    
    const titleInput = document.getElementById('board-add-title-input');
    if (!titleInput) return;
    
    const title = titleInput.value.trim();
    if (title === '') {
        alert('Lütfen sütun başlığı girin.');
        return;
    }
    
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('t', title);
    
    fetch(basePath + '/kanban-board-ekle', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeSheet('mobile-board-add-sheet');
            if (typeof window.showToast === 'function') {
                window.showToast({ category: 'success', title: 'Başarılı', description: 'Sütun eklendi.' });
            } else {
                alert('Sütun eklendi.');
            }
            setTimeout(() => { window.location.reload(); }, 600);
        } else {
            alert(data.error || 'Sütun eklenemedi.');
        }
    })
    .catch(err => {
        alert('Sunucuyla bağlantı kurulamadı.');
    });
}

// Carousel horizontal scrolling navigations
function scrollMobileKanban(direction) {
    const container = document.getElementById('mobile-tasks-container');
    if (!container) return;
    
    const offset = window.innerWidth - 16;
    const targetScroll = container.scrollLeft + (direction === 'left' ? -offset : offset);
    
    container.scrollTo({
        left: targetScroll,
        behavior: 'smooth'
    });
}

function updateMobileKanbanNavButtons() {
    const container = document.getElementById('mobile-tasks-container');
    const prevBtn = document.getElementById('mobile-kanban-prev-btn');
    const nextBtn = document.getElementById('mobile-kanban-next-btn');
    if (!container || !prevBtn || !nextBtn) return;
    
    const scrollLeft = container.scrollLeft;
    const maxScrollLeft = container.scrollWidth - container.clientWidth;
    
    if (scrollLeft <= 10) {
        prevBtn.classList.add('opacity-0', 'pointer-events-none');
    } else {
        prevBtn.classList.remove('opacity-0', 'pointer-events-none');
    }
    
    if (scrollLeft >= maxScrollLeft - 10) {
        nextBtn.classList.add('opacity-0', 'pointer-events-none');
    } else {
        nextBtn.classList.remove('opacity-0', 'pointer-events-none');
    }
}

// Premium Touch-based Drag & Drop for Mobile
let touchDraggedNode = null;
let touchClone = null;
let touchStartX = 0;
let touchStartY = 0;
let isTouchDragging = false;
let touchScrollInterval = null;

function initMobileTouchDrag() {
    const container = document.getElementById('mobile-tasks-container');
    if (!container) return;
    
    container.querySelectorAll('.mobile-task-card').forEach(card => {
        card.removeEventListener('touchstart', handleTouchStart);
        card.removeEventListener('touchmove', handleTouchMove);
        card.removeEventListener('touchend', handleTouchEnd);
        
        card.addEventListener('touchstart', handleTouchStart, { passive: false });
        card.addEventListener('touchmove', handleTouchMove, { passive: false });
        card.addEventListener('touchend', handleTouchEnd, { passive: false });
    });
}

function handleTouchStart(e) {
    if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('label') || e.target.closest('span')) return;
    
    const card = e.currentTarget;
    touchDraggedNode = card;
    
    const touch = e.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    isTouchDragging = false;
}

function handleTouchMove(e) {
    if (!touchDraggedNode) return;
    
    const touch = e.touches[0];
    const diffX = touch.clientX - touchStartX;
    const diffY = touch.clientY - touchStartY;
    
    if (!isTouchDragging && (Math.abs(diffX) > 12 || Math.abs(diffY) > 12)) {
        isTouchDragging = true;
        
        touchClone = touchDraggedNode.cloneNode(true);
        touchClone.style.position = 'fixed';
        touchClone.style.width = touchDraggedNode.offsetWidth + 'px';
        touchClone.style.opacity = '0.9';
        touchClone.style.pointerEvents = 'none';
        touchClone.style.zIndex = '9999';
        touchClone.style.transform = 'scale(1.04)';
        touchClone.style.boxShadow = '0 20px 25px -5px rgb(0 0 0 / 0.15), 0 8px 10px -6px rgb(0 0 0 / 0.15)';
        document.body.appendChild(touchClone);
        
        touchDraggedNode.style.opacity = '0.3';
    }
    
    if (isTouchDragging) {
        e.preventDefault();
        
        touchClone.style.left = (touch.clientX - touchClone.offsetWidth / 2) + 'px';
        touchClone.style.top = (touch.clientY - touchClone.offsetHeight / 2) + 'px';
        
        const edgeThreshold = 60;
        const container = document.getElementById('mobile-tasks-container');
        if (container) {
            clearInterval(touchScrollInterval);
            if (touch.clientX < edgeThreshold) {
                touchScrollInterval = setInterval(() => {
                    container.scrollLeft -= 10;
                }, 16);
            } else if (touch.clientX > window.innerWidth - edgeThreshold) {
                touchScrollInterval = setInterval(() => {
                    container.scrollLeft += 10;
                }, 16);
            }
        }
    }
}

function handleTouchEnd(e) {
    clearInterval(touchScrollInterval);
    
    if (!touchDraggedNode) return;
    
    if (isTouchDragging) {
        e.preventDefault();
        
        const touch = e.changedTouches[0];
        if (touchClone) {
            touchClone.remove();
            touchClone = null;
        }
        
        touchDraggedNode.style.opacity = '';
        
        const element = document.elementFromPoint(touch.clientX, touch.clientY);
        const targetColumn = element ? element.closest('.mobile-status-list') : null;
        
        if (targetColumn) {
            const targetBoardId = targetColumn.id.replace('col-list-', '');
            const sourceBoardId = touchDraggedNode.getAttribute('data-board-id');
            const taskId = touchDraggedNode.getAttribute('data-id');
            
            if (parseInt(targetBoardId) !== parseInt(sourceBoardId)) {
                targetColumn.appendChild(touchDraggedNode);
                touchDraggedNode.setAttribute('data-board-id', targetBoardId);
                
                updateMobileTaskStatus(taskId, targetBoardId);
            }
        }
    }
    
    touchDraggedNode = null;
    isTouchDragging = false;
}

// Initializes swiping gestures on list loading
if (typeof initSwipeActions === 'function') {
    initSwipeActions();
}
if (typeof initMobileTouchDrag === 'function') {
    initMobileTouchDrag();
}
// Initial buttons visibility check
setTimeout(() => {
    updateMobileKanbanNavButtons();
}, 200);

// Enable local flatpickr on mobile due date field
if (typeof flatpickr === 'function') {
    flatpickr("#mobile-form-date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d.m.Y",
        locale: "tr",
        disableMobile: "true"
    });
}

// Multi column view switcher (smooth scrolls to target column)
function showMobileColumn(boardId) {
    const targetCol = document.getElementById('col-list-' + boardId);
    const container = document.getElementById('mobile-tasks-container');
    if (targetCol && container) {
        container.scrollTo({
            left: targetCol.offsetLeft - container.offsetLeft,
            behavior: 'smooth'
        });
    }
    
    if (typeof initSwipeActions === 'function') {
        initSwipeActions();
    }
}

// Reload tab scope filter
function switchMobileFilter(filterVal) {
    switchTab('kanban', null, { filter: filterVal });
}

// Collapsible search action
function toggleMobileSearch() {
    const row = document.getElementById('mobile-search-row');
    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
        document.getElementById('kanban-search-input').focus();
    } else {
        row.classList.add('hidden');
        document.getElementById('kanban-search-input').value = '';
        filterKanbanCards();
    }
}

// Client side local search filter
function filterKanbanCards() {
    const term = document.getElementById('kanban-search-input').value.toLowerCase().trim();
    document.querySelectorAll('.mobile-task-card').forEach(card => {
        const title = card.getAttribute('data-title').toLowerCase();
        const desc = card.getAttribute('data-desc').toLowerCase();
        if (title.includes(term) || desc.includes(term)) {
            card.style.setProperty('display', '', 'important');
        } else {
            card.style.setProperty('display', 'none', 'important');
        }
    });
}

// Open Ekle Bottom Sheet
function openMobileEkleSheet(boardId = 0) {
    document.getElementById('mobile-sheet-title').innerText = "Yeni Görev Ekle";
    document.getElementById('mobileTaskForm').reset();
    document.getElementById('mobile-form-id').value = "";
    
    const boardSelect = document.getElementById('mobile-form-board-id');
    if (boardSelect && boardId > 0) {
        boardSelect.value = boardId;
    }
    
    // Clear assignees check
    document.querySelectorAll('#mobile-task-sheet input[type="checkbox"]').forEach(cb => cb.checked = false);

    openSheet('mobile-task-sheet');
}

// Open Edit Sheet
function openMobileEditSheet(card) {
    document.getElementById('mobile-sheet-title').innerText = "Görevi Düzenle";
    document.getElementById('mobile-form-id').value = card.getAttribute('data-id');
    
    const boardVal = card.getAttribute('data-board-id');
    const boardSelect = document.getElementById('mobile-form-board-id');
    if (boardSelect && boardVal) {
        boardSelect.value = boardVal;
    }
    
    document.getElementById('mobile-form-title').value = card.getAttribute('data-title');
    document.getElementById('mobile-form-desc').value = card.getAttribute('data-desc');
    document.getElementById('mobile-form-priority').value = card.getAttribute('data-priority');

    const dVal = card.getAttribute('data-date');
    const dInput = document.getElementById('mobile-form-date');
    dInput.value = dVal;
    if (dInput._flatpickr) {
        dInput._flatpickr.setDate(dVal);
    }

    // Set correct collaborator checkboxes
    const assIds = JSON.parse(card.getAttribute('data-assignees') || '[]');
    const assIdStrings = assIds.map(id => String(id));
    document.querySelectorAll('#mobile-task-sheet input[type="checkbox"]').forEach(cb => {
        cb.checked = assIdStrings.includes(String(cb.value));
    });

    openSheet('mobile-task-sheet');
}

// Handle Form Submission via Fetch
document.getElementById('mobileTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const id = document.getElementById('mobile-form-id').value;
    const basePath = '<?php echo appBasePath(); ?>';
    const url = id ? (basePath + '/kanban-gorev-guncelle') : (basePath + '/kanban-gorev-ekle');
    
    const formData = new FormData(this);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeAllSheets();
            showMobileNotification(data.message || 'Görev kaydedildi.');
            setTimeout(() => {
                switchTab('kanban', null, { filter: '<?php echo $filter; ?>' });
            }, 500);
        } else {
            showMobileNotification(data.error || 'Hata oluştu.', 'error');
        }
    })
    .catch(err => {
        showMobileNotification('Sunucu bağlantı hatası.', 'error');
    });
});

// Fast swipe right status transition handler (Shifts task to next board)
function updateMobileTaskStatus(id, newBoardId) {
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', id);
    formData.append('board_id', newBoardId);

    fetch(basePath + '/kanban-gorev-durum-guncelle', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showMobileNotification('Görevin yeri güncellendi.');
            setTimeout(() => {
                switchTab('kanban', null, { filter: '<?php echo $filter; ?>' });
            }, 400);
        } else {
            showMobileNotification(data.error || 'İşlem yapılamadı.', 'error');
        }
    })
    .catch(err => {
        showMobileNotification('İletişim hatası.', 'error');
    });
}

// Delete task
function deleteMobileTask(id) {
    if (!confirm('Görev tamamen silinecek. Emin misiniz?')) return;

    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', id);

    fetch(basePath + '/kanban-gorev-sil', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showMobileNotification('Görev silindi.');
            setTimeout(() => {
                switchTab('kanban', null, { filter: '<?php echo $filter; ?>' });
            }, 400);
        } else {
            showMobileNotification(data.error || 'Hata oluştu.', 'error');
        }
    })
    .catch(err => {
        showMobileNotification('Hata oluştu.', 'error');
    });
}

// Simple mobile helper alerts
function showMobileNotification(msg, type = 'success') {
    if (window.showToast) {
        window.showToast({
            category: type,
            title: type === 'success' ? 'Başarılı' : 'Hata',
            description: msg
        });
    } else {
        alert(msg);
    }
}
</script>
