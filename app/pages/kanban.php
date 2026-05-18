<?php
$pageTitle = "Kanban Board";
$pageSubtitle = "Kurum içi iş birliği, görev atamaları ve dinamik Kanban panosu.";
$currentUserId = $_SESSION['user_id'] ?? 0;
$colorMap = ['bg-indigo-500 text-indigo-50', 'bg-emerald-500 text-emerald-50', 'bg-amber-500 text-amber-50', 'bg-rose-500 text-rose-50', 'bg-teal-500 text-teal-50', 'bg-blue-500 text-blue-50', 'bg-purple-500 text-purple-50', 'bg-pink-500 text-pink-50'];
?>

<div class="space-y-6 animate-fade-in pr-1" id="kanban-app-container">
    <!-- Top Row: Title & Team Avatars -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-200/50 dark:border-zinc-800/40 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Kanban Board</h1>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Dinamik sütunlar oluşturun, görevleri sürükleyin ve ekibinizle koordineli çalışın.</p>
        </div>
        
        <!-- Team Members Avatar filter bar -->
        <div class="flex items-c            <span class="text-xs font-bold text-zinc-400 dark:text-zinc-550 uppercase tracking-wider">Ekip Filtresi:</span>
            <div class="flex -space-x-1.5 overflow-hidden items-center" id="header-avatar-filter-list">
                <?php foreach ($users as $u): 
                    $uParts = preg_split('/\s+/', trim($u['name']));
                    $init = strtoupper(substr($uParts[0] ?? 'U', 0, 1) . substr($uParts[1] ?? 'S', 0, 1));
                    $cIdx = abs(crc32($u['name'])) % count($colorMap);
                    $cClass = $colorMap[$cIdx];
                ?>
                    <button onclick="toggleUserFilter(<?= $u['id'] ?>, this)" 
                            class="inline-flex size-8.5 items-center justify-center rounded-full <?= $cClass ?> border-2 border-white dark:border-zinc-950 text-[10px] font-extrabold uppercase shrink-0 shadow-sm transition-all duration-200 cursor-pointer hover:-translate-y-0.5 focus:outline-none"
                            id="filter-avatar-<?= $u['id'] ?>"
                            title="<?= htmlspecialchars($u['name']) ?>">
                        <?= $init ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <button onclick="clearAllFilters()" class="btn-outline !h-8.5 !px-3 !text-xs !font-bold gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-850 shrink-0" id="clear-filters-btn" style="display: none;">
                Filtreleri Temizle
            </button>     </button>
        </div>
    </div>

    <!-- Secondary Control Sub-Header: View selectors & Search/Filters/Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <!-- View selector tabs -->
        <div class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800/60 p-1 text-zinc-500 border border-zinc-200/50 dark:border-zinc-700/40">
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-bold transition-all bg-white dark:bg-zinc-900 text-zinc-950 dark:text-zinc-50 shadow-sm">
                Pano
            </button>
            <button onclick="showNotification('Liste görünümü çok yakında hizmetinizde!', 'info')" class="inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-bold transition-all hover:text-zinc-900 dark:hover:text-zinc-300 cursor-pointer">
                Liste
            </button>
            <button onclick="showNotification('Tablo görünümü çok yakında hizmetinizde!', 'info')" class="inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-bold transition-all hover:text-zinc-900 dark:hover:text-zinc-300 cursor-pointer">
                Tablo
            </button>
        </div>

        <!-- Search, Filters, and Add Board controls -->
        <div class="flex items-center gap-2.5 self-stretch sm:self-auto justify-end">
            <!-- Search bar -->
            <div class="relative w-full sm:w-60">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="task-search-input" oninput="filterTasks()" placeholder="Görevlerde ara..." class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-850 rounded-lg text-xs font-semibold focus:outline-none focus:border-zinc-400 dark:focus:border-zinc-700 transition-colors">
            </div>

            <!-- Custom Priority Filter Popover Dropdown -->
            <div class="relative" id="filter-priority-container">
                <button onclick="togglePriorityFilterPopover(event)" id="filter-priority-trigger" class="btn-outline !h-8.5 !px-3 text-xs font-bold gap-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-850">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <span>Filtrele</span>
                </button>
                
                <div id="filter-priority-popover" class="absolute right-0 mt-1.5 w-48 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl p-1 z-50 hidden">
                    <div class="p-2 border-b border-zinc-150 dark:border-zinc-800/80 mb-1">
                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-zinc-400">Öncelik Filtresi</span>
                    </div>
                    <button onclick="setPriorityFilter('all', this)" class="w-full text-left px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-xs font-bold flex items-center justify-between text-zinc-900 dark:text-zinc-100">
                        <span>Tümü</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" class="text-zinc-500 filter-check-icon" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button onclick="setPriorityFilter('high', this)" class="w-full text-left px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-xs font-semibold flex items-center justify-between text-rose-600 dark:text-rose-400">
                        <span>Yüksek</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" class="text-rose-600 filter-check-icon hidden" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button onclick="setPriorityFilter('medium', this)" class="w-full text-left px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-xs font-semibold flex items-center justify-between text-amber-600 dark:text-amber-400">
                        <span>Orta</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" class="text-amber-600 filter-check-icon hidden" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button onclick="setPriorityFilter('low', this)" class="w-full text-left px-2.5 py-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded text-xs font-semibold flex items-center justify-between text-blue-600 dark:text-blue-400">
                        <span>Düşük</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" class="text-blue-600 filter-check-icon hidden" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                </div>
            </div>

            <!-- Create New Board Column Button -->
            <button onclick="openAddBoardDialog()" class="inline-flex h-8.5 items-center justify-center rounded-lg bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-950 dark:hover:bg-zinc-50/90 px-3.5 text-xs font-bold transition-all shadow-sm gap-1.5 cursor-pointer active:scale-95 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
                Sütun Ekle
            </button>
        </div>
    </div>

    <!-- Kanban Dynamic Board Scroll Container -->
    <div class="flex items-start gap-5 overflow-x-auto pb-4 pt-1 custom-scrollbar w-full min-h-[calc(100vh-270px)]" id="kanban-columns-scroller" ondragover="allowBoardDrop(event)">
        <!-- Dynamic Columns -->
        <?php foreach ($boards as $board): 
            $boardTasks = array_filter($tasks, fn($t) => intval($t['board_id'] ?? 0) === intval($board['id']));
            $taskCount = count($boardTasks);
        ?>
            <!-- Column Container (Draggable to sort) -->
            <div class="w-[290px] shrink-0 bg-zinc-50/30 dark:bg-zinc-900/10 border border-zinc-200/60 dark:border-zinc-800/80 rounded-xl shadow-sm flex flex-col max-h-[calc(100vh-290px)] overflow-hidden transition-all duration-200 column-node"
                 draggable="true"
                 ondragstart="dragBoard(event)"
                 ondragend="dragBoardEnd(event)"
                 ondragover="allowBoardDropOver(event)"
                 ondrop="dropBoard(event)"
                 data-board-id="<?= $board['id'] ?>">
                
                <!-- Board Header (Handles the board drag) -->
                <div class="px-4 py-3.5 flex items-center justify-between border-b border-zinc-200/50 dark:border-zinc-800/40 bg-zinc-50/70 dark:bg-zinc-900/40 cursor-grab active:cursor-grabbing">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        <!-- Grab Handle visual indicator -->
                        <span class="text-zinc-400 dark:text-zinc-600 text-xs font-bold leading-none shrink-0 cursor-grab">::</span>
                        <h3 class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight truncate cursor-pointer hover:underline" 
                            title="Çift tıklayarak düzenleyebilirsiniz"
                            ondblclick="startRenameBoard(<?= $board['id'] ?>, this)"
                            id="board-title-text-<?= $board['id'] ?>"><?= htmlspecialchars($board['title']) ?></h3>
                        <span class="text-xs font-black px-1.5 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-650 dark:text-zinc-400 border border-zinc-200/30 dark:border-zinc-700/20 count-badge shrink-0" id="badge-board-<?= $board['id'] ?>">
                            <?= $taskCount ?>
                        </span>
                    </div>

                    <!-- Actions (Edit & Delete Column Buttons) -->
                    <div class="flex items-center gap-1 shrink-0 ml-2">
                        <button onclick="startRenameBoard(<?= $board['id'] ?>, document.getElementById('board-title-text-<?= $board['id'] ?>'))" 
                                class="p-1 rounded text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                                title="Sütun Başlığını Düzenle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                        <button onclick="deleteBoard(<?= $board['id'] ?>, '<?= htmlspecialchars($board['title']) ?>')" 
                                class="p-1 rounded text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-colors cursor-pointer"
                                title="Sütunu Sil">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Cards list droppable viewport -->
                <div class="flex-1 p-3 overflow-y-auto space-y-3 custom-scrollbar min-h-[150px] tasks-drop-zone transition-colors" 
                     id="board-tasks-list-<?= $board['id'] ?>"
                     ondragover="allowTaskDrop(event)"
                     ondrop="dropTask(event, <?= $board['id'] ?>)">
                    
                    <?php foreach ($boardTasks as $task): 
                        // Overdue calculation
                        $isOverdue = false;
                        if (!empty($task['due_date'])) {
                            $isOverdue = (strtotime($task['due_date']) < strtotime(date('Y-m-d')));
                        }
                        
                        // Pick random mock attachments/comments count to closely resemble screenshot
                        $mockAttachments = (abs(crc32($task['title'])) % 3) + 1;
                        $mockComments = (abs(crc32($task['title'] . '_cmt')) % 5) + 1;
                        
                        // Deterministic mock percentages
                        $mockPercent = 0;
                        if ($board['title'] === 'Done' || stripos($board['title'], 'tamam') !== false) {
                            $mockPercent = 100;
                        } elseif ($board['title'] === 'In Progress' || stripos($board['title'], 'yapılıyor') !== false || stripos($board['title'], 'süreç') !== false) {
                            $mockPercent = (abs(crc32($task['title'] . '_pct')) % 4 + 4) * 10; // 40%, 50%, 60%, 70%
                        } else {
                            $mockPercent = (abs(crc32($task['title'] . '_pct')) % 3) * 10; // 0%, 10%, 20%
                        }
                    ?>
                        <!-- Task Card (Draggable inside columns) -->
                        <div class="group/card bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl p-3.5 shadow-sm hover:shadow-md transition-all duration-200 cursor-grab active:cursor-grabbing flex flex-col justify-between min-h-[135px] space-y-3 card-node"
                             draggable="true"
                             ondragstart="dragTask(event)"
                             ondragend="dragTaskEnd(event)"
                             id="task-card-<?= $task['id'] ?>"
                             data-task-id="<?= $task['id'] ?>"
                             data-task-title="<?= htmlspecialchars($task['title']) ?>"
                             data-task-desc="<?= htmlspecialchars($task['description']) ?>"
                             data-task-priority="<?= $task['priority'] ?>"
                             data-task-date="<?= $task['due_date'] ?>"
                             data-task-board="<?= $board['id'] ?>"
                             data-task-assignees="<?= htmlspecialchars(json_encode(array_column($task['assignees'], 'id'))) ?>">
                            <div class="space-y-1">
                                <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-50 leading-snug tracking-tight"><?= htmlspecialchars($task['title']) ?></h4>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 leading-relaxed line-clamp-2"><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Assignees & Progress Row -->
                            <div class="flex items-center justify-between">
                                <!-- Overlapping Assignees -->
                                <div class="flex -space-x-1.5 overflow-hidden items-center shrink-0">
                                    <?php foreach ($task['assignees'] as $asn): 
                                        $initials = '';
                                        $parts = preg_split('/\s+/', trim($asn['name']));
                                        $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[1] ?? 'S', 0, 1));
                                        $colorIndex = abs(crc32($asn['name'])) % count($colorMap);
                                        $colorClass = $colorMap[$colorIndex];
                                    ?>
                                        <div class="inline-flex size-6.5 items-center justify-center rounded-full <?= $colorClass ?> border border-white dark:border-zinc-900 text-[10px] font-extrabold uppercase shrink-0 shadow-sm cursor-help"
                                             title="<?= htmlspecialchars($asn['name']) ?>">
                                            <?= $initials ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($task['assignees'])): ?>
                                        <span class="text-xs font-medium text-zinc-450 dark:text-zinc-555 italic">Atanmamış</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Progress Percentage badge matching mockup -->
                                <div class="flex items-center shrink-0">
                                    <?php if ($mockPercent === 100): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-1.5 py-0.5 rounded-full border border-emerald-100 dark:border-emerald-900/40">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" fill="none" stroke="currentColor" stroke-width="3.5" class="shrink-0" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                            100%
                                        </span>
                                    <?php elseif ($mockPercent >= 40): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-1.5 py-0.5 rounded-full border border-amber-100 dark:border-amber-900/40 animate-pulse">
                                            <?= $mockPercent ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800 px-1.5 py-0.5 rounded-full border border-zinc-200 dark:border-zinc-700">
                                            <?= $mockPercent ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Card Footer: Priority, Due Date, and Mock Stats -->
                            <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800/40 pt-2 bg-transparent">
                                <div class="flex items-center gap-1.5">
                                    <!-- Priority Badge -->
                                    <?php if ($task['priority'] === 'high'): ?>
                                        <span class="text-[10px] font-bold tracking-wider uppercase px-1.5 py-0.5 rounded bg-zinc-50 dark:bg-zinc-800 text-rose-600 dark:text-rose-400 border border-zinc-200 dark:border-zinc-700 shrink-0">Yüksek</span>
                                    <?php elseif ($task['priority'] === 'medium'): ?>
                                        <span class="text-[10px] font-bold tracking-wider uppercase px-1.5 py-0.5 rounded bg-zinc-50 dark:bg-zinc-800 text-amber-600 dark:text-amber-400 border border-zinc-200 dark:border-zinc-700 shrink-0">Orta</span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-bold tracking-wider uppercase px-1.5 py-0.5 rounded bg-zinc-50 dark:bg-zinc-800 text-blue-600 dark:text-blue-400 border border-zinc-200 dark:border-zinc-700 shrink-0">Düşük</span>
                                    <?php endif; ?>

                                    <!-- Mock Indicators matching screenshot layout -->
                                    <div class="flex items-center gap-1 text-zinc-400 text-[10px] font-bold shrink-0">
                                        <span>📎 <?= $mockAttachments ?></span>
                                        <span>💬 <?= $mockComments ?></span>
                                    </div>
                                </div>

                                <!-- Action Buttons row (Fades in on hover) -->
                                <div class="opacity-0 group-hover/card:opacity-100 transition-all duration-150 flex items-center gap-1 shrink-0 bg-white dark:bg-zinc-900 pl-1.5">
                                    <button onclick="openEditTaskModal(this)" class="p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer" title="Düzenle">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button onclick="deleteTask(<?= $task['id'] ?>)" class="p-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors cursor-pointer" title="Sil">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($boardTasks)): ?>
                        <div class="py-10 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col items-center justify-center text-center space-y-2 text-zinc-400 no-tasks-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            <span class="text-xs font-black uppercase tracking-wider">Burada henüz görev yok</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add Task trigger at bottom of board -->
                <div class="p-3 border-t border-zinc-200/50 dark:border-zinc-800/40 bg-zinc-50/30 dark:bg-zinc-900/10">
                    <button onclick="openAddTaskModal(<?= $board['id'] ?>)" class="w-full flex items-center justify-center py-2 border border-zinc-200/60 dark:border-zinc-800 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100/50 dark:hover:bg-zinc-850 rounded-lg text-xs font-bold tracking-tight transition-colors cursor-pointer active:scale-[0.98]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" class="mr-1.5" viewBox="0 0 24 24"><path d="M5 12h14m-7-7v14"/></svg>
                        Görev Ekle
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ==========================================
     MODALS & DIALOGS
     ========================================== -->

<?php
$boardOptions = array_map(fn($b) => ['value' => $b['id'], 'label' => $b['title']], $boards);
$priorityOptions = [
    ['value' => 'low', 'label' => 'Düşük'],
    ['value' => 'medium', 'label' => 'Orta'],
    ['value' => 'high', 'label' => 'Yüksek']
];
?>

<!-- 1. Add Board Column Dialog -->
<dialog id="board-dialog" class="dialog" style="max-width: 450px; width: 90vw;" onclick="if (event.target === this) this.close()">
    <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
        <header class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Sütun (Board) Ekle</h2>
                <p class="text-sm text-zinc-500">Panonuza yeni bir sütun eklemek için başlığı girin.</p>
            </div>
            <button onclick="document.getElementById('board-dialog').close()" class="text-zinc-400 hover:text-zinc-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                </svg>
            </button>
        </header>
        
        <section>
            <form id="boardForm" class="form grid gap-4">
                <div class="grid gap-2">
                    <label for="board-title-input">Sütun Başlığı</label>
                    <input type="text" id="board-title-input" required placeholder="Örn: QA Review">
                </div>
            </form>
        </section>
        
        <footer>
            <button type="button" class="btn-outline" onclick="document.getElementById('board-dialog').close()">Vazgeç</button>
            <button type="submit" form="boardForm" class="btn">Oluştur</button>
        </footer>
    </div>
</dialog>

<!-- 2. Task Form Dialog Modal -->
<dialog id="task-dialog" class="dialog" style="max-width: 550px; width: 90vw;" onclick="if (event.target === this) this.close()">
    <div class="dialog-content bg-white dark:bg-zinc-900 p-6 rounded-xl shadow-2xl" onclick="event.stopPropagation()">
        <header class="flex items-start justify-between mb-6">
            <div>
                <h2 id="task-modal-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Yeni Görev Ekle</h2>
                <p class="text-sm text-zinc-500">Görev detaylarını girmek ve atamaları yapmak için formu doldurun.</p>
            </div>
            <button onclick="document.getElementById('task-dialog').close()" class="text-zinc-400 hover:text-zinc-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                </svg>
            </button>
        </header>

        <section>
            <form id="taskForm" class="form grid gap-4">
                <input type="hidden" name="id" id="form-task-id" value="">
                
                <!-- Title -->
                <div class="grid gap-2">
                    <label for="form-task-title">Görev Başlığı <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" id="form-task-title" required placeholder="Aylık 4B dilekçelerini incele">
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <label for="form-task-desc">Açıklama</label>
                    <textarea name="description" id="form-task-desc" rows="3" placeholder="Görev detayları veya alt maddeler..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Priority Custom Select -->
                    <div class="grid gap-2">
                        <label>Öncelik</label>
                        <?php echo renderCustomSelect('form-task-priority-select', 'priority', $priorityOptions, 'medium', 'w-full'); ?>
                    </div>

                    <!-- Sütun Mapping -->
                    <div class="grid gap-2">
                        <label>Sütun (Board)</label>
                        <?php echo renderCustomSelect('form-task-board-select', 'board_id', $boardOptions, $boards[0]['id'] ?? '', 'w-full'); ?>
                    </div>
                </div>

                <!-- Son Tarih Datepicker field -->
                <div class="grid gap-2">
                    <label for="form-task-date">Son Tarih</label>
                    <input type="text" name="due_date" id="form-task-date" placeholder="Tarih Seçin" class="datepicker">
                </div>

                <!-- Assignees (Checklist representation) -->
                <div class="grid gap-2">
                    <label>Kurum İçi Görevliler (Atananlar)</label>
                    <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 max-h-36 overflow-y-auto space-y-2 bg-zinc-50/50 dark:bg-zinc-950/20 custom-scrollbar">
                        <?php if (empty($users)): ?>
                            <p class="text-[10px] text-zinc-400 font-semibold italic text-center py-2">Kurumda başka kullanıcı bulunamadı.</p>
                        <?php else: ?>
                            <?php foreach ($users as $u): 
                                $init = '';
                                $uParts = preg_split('/\s+/', trim($u['name']));
                                $init = strtoupper(substr($uParts[0] ?? 'U', 0, 1) . substr($uParts[1] ?? 'S', 0, 1));
                                $cIdx = abs(crc32($u['name'])) % count($colorMap);
                                $cClass = $colorMap[$cIdx];
                            ?>
                                <label class="flex items-center gap-3 cursor-pointer p-2.5 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/40 active:bg-zinc-100/70 transition-all text-xs font-semibold text-zinc-800 dark:text-zinc-200 w-full">
                                    <input type="checkbox" name="assignees[]" value="<?= $u['id'] ?>" class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-700 text-zinc-900 focus:ring-zinc-900 shrink-0">
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
            </form>
        </section>

        <footer>
            <button type="button" class="btn-outline" onclick="document.getElementById('task-dialog').close()">Vazgeç</button>
            <button type="submit" form="taskForm" class="btn">Kaydet</button>
        </footer>
    </div>
</dialog>

<!-- ==========================================
     PREMIUM SCRIPTS AND INTERACTIONS
     ========================================== -->
<script>
// Filter state variables
let selectedUserFilterId = null;
let activePriorityFilter = 'all';

document.addEventListener('DOMContentLoaded', function() {
    // Task Form AJAX submit handler
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('form-task-id').value;
        const basePath = '<?php echo appBasePath(); ?>';
        const url = id ? (basePath + '/kanban-gorev-guncelle') : (basePath + '/kanban-gorev-ekle');
        
        const formData = new FormData(this);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Görev başarıyla kaydedildi.');
                document.getElementById('task-dialog').close();
                setTimeout(() => { window.location.reload(); }, 600);
            } else {
                showNotification(data.error || 'İşlem başarısız.', 'error');
            }
        })
        .catch(err => {
            showNotification('Sunucu bağlantısı koptu.', 'error');
        });
    });

    // Board Form AJAX submit handler
    document.getElementById('boardForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const titleInput = document.getElementById('board-title-input');
        const basePath = '<?php echo appBasePath(); ?>';
        
        const formData = new FormData();
        formData.append('title', titleInput.value.trim());

        fetch(basePath + '/kanban-board-ekle', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Sütun başarıyla oluşturuldu.');
                document.getElementById('board-dialog').close();
                setTimeout(() => { window.location.reload(); }, 600);
            } else {
                showNotification(data.error || 'Sütun oluşturulamadı.', 'error');
            }
        })
        .catch(err => {
            showNotification('Sütun eklenirken sunucu hatası oluştu.', 'error');
        });
    });

    // Close filters if clicked outside
    document.addEventListener('click', function(e) {
        const filterTrigger = document.getElementById('filter-priority-trigger');
        const filterPopover = document.getElementById('filter-priority-popover');
        if (filterTrigger && filterPopover && !filterTrigger.contains(e.target) && !filterPopover.contains(e.target)) {
            filterPopover.classList.add('hidden');
        }
    });
});

// Toggle Priority Filter Popover
function togglePriorityFilterPopover(e) {
    e.stopPropagation();
    const popover = document.getElementById('filter-priority-popover');
    popover.classList.toggle('hidden');
}

// Set Priority filter
function setPriorityFilter(priority, el) {
    activePriorityFilter = priority;
    
    // Update active visual check icons
    const popover = document.getElementById('filter-priority-popover');
    popover.querySelectorAll('.filter-check-icon').forEach(icon => icon.classList.add('hidden'));
    
    if (el) {
        el.querySelector('.filter-check-icon').classList.remove('hidden');
    }
    
    // Hide popover
    popover.classList.add('hidden');
    
    // Show clear filters button if filter is active
    updateClearFiltersVisibility();
    
    // Run filter
    filterTasks();
}

// Toggle Team Avatar Filter
function toggleUserFilter(userId, btn) {
    const list = document.getElementById('header-avatar-filter-list');
    
    if (selectedUserFilterId === userId) {
        // Deselect
        selectedUserFilterId = null;
        btn.classList.remove('scale-110', 'ring-2', 'ring-zinc-950', 'dark:ring-zinc-50', 'z-10');
    } else {
        // Reset old highlight
        list.querySelectorAll('button').forEach(b => {
            b.classList.remove('scale-110', 'ring-2', 'ring-zinc-950', 'dark:ring-zinc-50', 'z-10');
        });
        
        selectedUserFilterId = userId;
        btn.classList.add('scale-110', 'ring-2', 'ring-zinc-950', 'dark:ring-zinc-50', 'z-10');
    }

    updateClearFiltersVisibility();
    filterTasks();
}

// Clear all active filters
function clearAllFilters() {
    selectedUserFilterId = null;
    activePriorityFilter = 'all';
    document.getElementById('task-search-input').value = '';
    
    // Reset avatar highlights
    const list = document.getElementById('header-avatar-filter-list');
    list.querySelectorAll('button').forEach(b => {
        b.classList.remove('scale-110', 'ring-2', 'ring-zinc-950', 'dark:ring-zinc-50', 'z-10');
    });

    // Reset checkmark in priority list
    const popover = document.getElementById('filter-priority-popover');
    popover.querySelectorAll('.filter-check-icon').forEach(icon => icon.classList.add('hidden'));
    popover.querySelector('button[onclick*="all"]').querySelector('.filter-check-icon').classList.remove('hidden');

    updateClearFiltersVisibility();
    filterTasks();
}

// Shows/hides the quick clear filter button
function updateClearFiltersVisibility() {
    const clearBtn = document.getElementById('clear-filters-btn');
    const searchVal = document.getElementById('task-search-input').value.trim();
    
    if (selectedUserFilterId !== null || activePriorityFilter !== 'all' || searchVal.length > 0) {
        clearBtn.style.display = 'inline-flex';
    } else {
        clearBtn.style.display = 'none';
    }
}

// REACTIVE CLIENT-SIDE FILTER ENGINE
function filterTasks() {
    const searchVal = document.getElementById('task-search-input').value.trim().toLowerCase();
    const cards = document.querySelectorAll('.card-node');
    
    cards.forEach(card => {
        const title = card.getAttribute('data-task-title').toLowerCase();
        const desc = card.getAttribute('data-task-desc').toLowerCase();
        const priority = card.getAttribute('data-task-priority');
        const assignees = JSON.parse(card.getAttribute('data-task-assignees') || '[]');
        
        let match = true;
        
        // 1. Text Search matches title or desc
        if (searchVal.length > 0) {
            if (!title.includes(searchVal) && !desc.includes(searchVal)) {
                match = false;
            }
        }
        
        // 2. Priority Filter matches
        if (activePriorityFilter !== 'all') {
            if (priority !== activePriorityFilter) {
                match = false;
            }
        }
        
        // 3. User Avatar Filter matches
        if (selectedUserFilterId !== null) {
            const assigneeStrings = assignees.map(id => String(id));
            if (!assigneeStrings.includes(String(selectedUserFilterId))) {
                match = false;
            }
        }
        
        // Render result instantly with smooth fade classes
        if (match) {
            card.style.display = 'flex';
            card.classList.remove('opacity-25', 'pointer-events-none');
        } else {
            card.style.display = 'none';
            card.classList.add('opacity-25', 'pointer-events-none');
        }
    });

    // Update column counters
    updateVisualColumnCounts();
    updateClearFiltersVisibility();
}

// Updates visual counts next to header titles after filtering
function updateVisualColumnCounts() {
    const columns = document.querySelectorAll('.column-node');
    columns.forEach(col => {
        const boardId = col.getAttribute('data-board-id');
        const visibleCards = col.querySelectorAll('.card-node:not([style*="display: none"])').length;
        const badge = col.querySelector('.count-badge');
        if (badge) badge.innerText = visibleCards;
        
        // Show/hide placeholder if column is completely empty
        const totalVisible = col.querySelectorAll('.card-node:not([style*="display: none"])').length;
        const placeholder = col.querySelector('.no-tasks-placeholder');
        if (totalVisible === 0) {
            if (!placeholder) {
                const listContainer = col.querySelector('.tasks-drop-zone');
                listContainer.insertAdjacentHTML('beforeend', `
                    <div class="py-10 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl flex flex-col items-center justify-center text-center space-y-2 text-zinc-400 no-tasks-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span class="text-xs font-black uppercase tracking-wider">Burada henüz görev yok</span>
                    </div>
                `);
            }
        } else {
            if (placeholder) placeholder.remove();
        }
    });
}

// Open Dynamic Board Column Creator Dialog
function openAddBoardDialog() {
    const dialog = document.getElementById('board-dialog');
    document.getElementById('board-title-input').value = '';
    dialog.showModal();
}

// Delete Dynamic Board Column
function deleteBoard(boardId, title) {
    event.stopPropagation();
    
    if (!confirm(`"${title}" sütununu ve içindeki TÜM GÖREVLERİ silmek istediğinize emin misiniz?`)) return;
    
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('board_id', boardId);

    fetch(basePath + '/kanban-board-sil', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Sütun silindi.');
            setTimeout(() => { window.location.reload(); }, 600);
        } else {
            showNotification(data.error || 'Sütun silinemedi.', 'error');
        }
    })
    .catch(err => {
        showNotification('Sütun silinirken bağlantı hatası oluştu.', 'error');
    });
}

// Helper function to set custom select value dynamically
function setCustomSelectValue(id, value) {
    const $component = $('#' + id);
    const $option = $component.find(`[role="option"][data-value="${value}"]`);
    if ($option.length > 0) {
        window.selectCustomOption(id, $option[0]);
    }
}

// Open Add Task Modal (Injects Board mapping)
function openAddTaskModal(boardId = 0) {
    const dialog = document.getElementById('task-dialog');
    document.getElementById('task-modal-title').innerText = "Yeni Görev Ekle";
    document.getElementById('taskForm').reset();
    document.getElementById('form-task-id').value = "";
    
    // Auto-select corresponding board option
    const finalBoardId = boardId > 0 ? boardId : (<?= json_encode($boards[0]['id'] ?? 0) ?>);
    setCustomSelectValue('form-task-board-select', finalBoardId);
    
    // Reset custom select
    setCustomSelectValue('form-task-priority-select', 'medium');
    
    // Clear all checked assignees
    dialog.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    dialog.showModal();
}

// Open Edit Task Modal
function openEditTaskModal(btn) {
    event.stopPropagation();
    const card = btn.closest('.card-node');
    const dialog = document.getElementById('task-dialog');
    
    document.getElementById('task-modal-title').innerText = "Görevi Düzenle";
    document.getElementById('form-task-id').value = card.getAttribute('data-task-id');
    document.getElementById('form-task-title').value = card.getAttribute('data-task-title');
    document.getElementById('form-task-desc').value = card.getAttribute('data-task-desc');
    
    const priorityVal = card.getAttribute('data-task-priority') || 'medium';
    setCustomSelectValue('form-task-priority-select', priorityVal);
    
    const boardVal = card.getAttribute('data-task-board');
    if (boardVal) {
        setCustomSelectValue('form-task-board-select', boardVal);
    }
    
    const dateVal = card.getAttribute('data-task-date');
    const dateInput = document.getElementById('form-task-date');
    dateInput.value = dateVal;
    if (dateInput._flatpickr) {
        dateInput._flatpickr.setDate(dateVal);
    }

    // Check correct assignees checkbox
    const assigneeIds = JSON.parse(card.getAttribute('data-task-assignees') || '[]');
    const assigneeIdStrings = assigneeIds.map(id => String(id));
    dialog.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.checked = assigneeIdStrings.includes(String(cb.value));
    });

    dialog.showModal();
}

// Delete Task AJAX Action
function deleteTask(id) {
    event.stopPropagation();
    
    if (!confirm('Bu görevi silmek istediğinize emin misiniz?')) return;

    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', id);

    fetch(basePath + '/kanban-gorev-sil', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Görev silindi.');
            const card = document.getElementById('task-card-' + id);
            if (card) {
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    card.remove();
                    updateVisualColumnCounts();
                }, 300);
            }
        } else {
            showNotification(data.error || 'Görev silinemedi.', 'error');
        }
    })
    .catch(err => {
        showNotification('Sunucu bağlantı hatası.', 'error');
    });
}

/* ==========================================
   DRAG AND DROP HANDLERS (CARDS & BOARDS)
   ========================================== */

let draggedNode = null;
let draggedNodeType = null; // 'task' or 'board'

// A. Task Drag Events
function dragTask(ev) {
    const card = ev.target.closest('.card-node');
    if (!card) return;
    draggedNode = card;
    draggedNodeType = 'task';
    ev.dataTransfer.setData("text/plain", card.id);
    card.classList.add('opacity-40');
    ev.stopPropagation(); // Prevent bubbling up to the column container
}

function dragTaskEnd(ev) {
    if (draggedNode && draggedNodeType === 'task') {
        draggedNode.classList.remove('opacity-40');
    }
    draggedNode = null;
    draggedNodeType = null;
    ev.stopPropagation(); // Prevent bubbling
}

function allowTaskDrop(ev) {
    if (draggedNodeType === 'task') {
        ev.preventDefault();
    }
}

function dropTask(ev, targetBoardId) {
    ev.preventDefault();
    if (!draggedNode || draggedNodeType !== 'task') return;
    
    const taskId = draggedNode.getAttribute('data-task-id');
    const oldBoardId = draggedNode.getAttribute('data-task-board');
    
    if (parseInt(oldBoardId) === parseInt(targetBoardId)) return;
    
    const listContainer = document.getElementById('board-tasks-list-' + targetBoardId);
    if (!listContainer) return;
    
    // Remove placeholder
    const placeholder = listContainer.querySelector('.no-tasks-placeholder');
    if (placeholder) placeholder.remove();
    
    // Perform visual change
    listContainer.appendChild(draggedNode);
    draggedNode.setAttribute('data-task-board', targetBoardId);
    
    // Sync to database
    const basePath = '<?php echo appBasePath(); ?>';
    const formData = new FormData();
    formData.append('id', taskId);
    formData.append('board_id', targetBoardId);

    fetch(basePath + '/kanban-gorev-durum-guncelle', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Görev sütunu güncellendi.');
            updateVisualColumnCounts();
        } else {
            showNotification(data.error || 'Sütun güncellenemedi.', 'error');
            setTimeout(() => { window.location.reload(); }, 600);
        }
    })
    .catch(err => {
        showNotification('Sütun güncellenirken sunucu hatası oluştu.', 'error');
        setTimeout(() => { window.location.reload(); }, 600);
    });
}

// B. Board (Column) Drag Events (Reordering columns)
function dragBoard(ev) {
    // Safety check: If dragging originated from a task card, DO NOT start column drag!
    if (ev.target.closest('.card-node')) {
        ev.preventDefault();
        return;
    }
    
    const board = ev.target.closest('.column-node');
    if (!board) return;
    
    draggedNode = board;
    draggedNodeType = 'board';
    ev.dataTransfer.setData("board_id", board.getAttribute('data-board-id'));
    board.classList.add('opacity-40', 'border-dashed', 'border-zinc-400');
}

function dragBoardEnd(ev) {
    if (draggedNode && draggedNodeType === 'board') {
        draggedNode.classList.remove('opacity-40', 'border-dashed', 'border-zinc-400');
    }
    draggedNode = null;
    draggedNodeType = null;
}

function allowBoardDropOver(ev) {
    if (draggedNodeType === 'board') {
        ev.preventDefault();
    }
}

function allowBoardDrop(ev) {
    if (draggedNodeType === 'board') {
        ev.preventDefault();
    }
}

function dropBoard(ev) {
    ev.preventDefault();
    if (!draggedNode || draggedNodeType !== 'board') return;
    
    const targetColumnNode = ev.target.closest('.column-node');
    if (!targetColumnNode || targetColumnNode === draggedNode) return;
    
    const scroller = document.getElementById('kanban-columns-scroller');
    
    // Determine reorder index placement (left or right)
    const listElements = Array.from(scroller.querySelectorAll('.column-node'));
    const draggedIndex = listElements.indexOf(draggedNode);
    const targetIndex = listElements.indexOf(targetColumnNode);
    
    if (draggedIndex < targetIndex) {
        scroller.insertBefore(draggedNode, targetColumnNode.nextSibling);
    } else {
        scroller.insertBefore(draggedNode, targetColumnNode);
    }
    
    // Collect new order and persist
    const newListElements = Array.from(scroller.querySelectorAll('.column-node'));
    const orders = {};
    newListElements.forEach((el, index) => {
        orders[el.getAttribute('data-board-id')] = index;
    });

    const basePath = '<?php echo appBasePath(); ?>';
    
    // Post to database reordering API
    const formData = new FormData();
    for (const [boardId, order] of Object.entries(orders)) {
        formData.append(`orders[${boardId}]`, order);
    }

    fetch(basePath + '/kanban-board-sira-guncelle', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Sütun sıralaması güncellendi.');
        } else {
            showNotification(data.error || 'Sıralama kaydedilemedi.', 'error');
        }
    })
    .catch(err => {
        showNotification('Sıralama kaydedilirken sunucu hatası oluştu.', 'error');
    });
}

// Helper notification toast bridge
function showNotification(msg, type = 'success') {
    if (window.showToast) {
        window.showToast({
            category: type,
            title: type === 'success' ? 'Başarılı' : (type === 'error' ? 'Hata' : 'Bilgi'),
            description: msg
        });
    } else {
        alert(msg);
    }
}

// Helper to update labels inside custom select components dynamically
function updateCustomSelectOptionLabel(selectId, optionValue, newLabel) {
    const selectEl = document.getElementById(selectId);
    if (!selectEl) return;
    
    // Find the option div with data-value matching optionValue
    const optionDiv = selectEl.querySelector(`div[role="option"][data-value="${optionValue}"]`);
    if (optionDiv) {
        // Update the span inside the option
        const labelSpan = optionDiv.querySelector('.option-label');
        if (labelSpan) labelSpan.innerText = newLabel;
    }
    
    // If the currently selected hidden value is equal to this option, update the trigger button's label too!
    const hiddenInput = document.getElementById(selectId + '-value');
    if (hiddenInput && parseInt(hiddenInput.value) === parseInt(optionValue)) {
        const triggerLabel = selectEl.querySelector('.selected-label');
        if (triggerLabel) triggerLabel.innerText = newLabel;
    }
}

// Inline Board Renaming Handler
let isRenamingBoard = false;

function startRenameBoard(boardId, h3Element) {
    if (isRenamingBoard) return;
    if (!h3Element || h3Element.querySelector('input')) return; // Already editing
    
    isRenamingBoard = true;
    const oldTitle = h3Element.innerText;
    
    // Create inline input field
    const input = document.createElement('input');
    input.type = 'text';
    input.value = oldTitle;
    input.className = 'w-full px-2 py-1 text-xs font-extrabold text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-100';
    
    // Clear title text and append input
    h3Element.innerHTML = '';
    h3Element.appendChild(input);
    input.focus();
    input.select();
    
    // Disable drag on the parent column to avoid conflict while typing
    const columnNode = h3Element.closest('.column-node');
    if (columnNode) columnNode.setAttribute('draggable', 'false');

    // Function to finish editing and save
    const finishRename = () => {
        if (!isRenamingBoard) return;
        const newTitle = input.value.trim();
        
        if (columnNode) columnNode.setAttribute('draggable', 'true');
        
        if (newTitle === '' || newTitle === oldTitle) {
            // Cancel rename, restore old title
            h3Element.innerHTML = '';
            h3Element.innerText = oldTitle;
            isRenamingBoard = false;
            return;
        }
        
        // Show loading state
        h3Element.innerText = 'Kaydediliyor...';
        
        const basePath = '<?php echo appBasePath(); ?>';
        const formData = new FormData();
        formData.append('board_id', boardId);
        formData.append('title', newTitle);
        
        fetch(basePath + '/kanban-board-baslik-guncelle', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                h3Element.innerHTML = '';
                h3Element.innerText = newTitle;
                showNotification('Sütun başlığı başarıyla güncellendi.');
                
                // Dynamically update the custom select in the DOM
                updateCustomSelectOptionLabel('form-task-board-select', boardId, newTitle);
                
                // Reload after a short delay to keep everything perfectly synchronized in PHP variables
                setTimeout(() => { window.location.reload(); }, 600);
            } else {
                h3Element.innerHTML = '';
                h3Element.innerText = oldTitle;
                showNotification(data.error || 'Güncelleme yapılamadı.', 'error');
            }
            isRenamingBoard = false;
        })
        .catch(err => {
            h3Element.innerHTML = '';
            h3Element.innerText = oldTitle;
            showNotification('Sunucu hatası oluştu.', 'error');
            isRenamingBoard = false;
        });
    };

    // Bind keydown events
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur(); // Triggers blur which runs finishRename
        } else if (e.key === 'Escape') {
            e.preventDefault();
            // Restore old title immediately without saving
            if (columnNode) columnNode.setAttribute('draggable', 'true');
            h3Element.innerHTML = '';
            h3Element.innerText = oldTitle;
            isRenamingBoard = false;
        }
    });

    // Bind blur event
    input.addEventListener('blur', function() {
        finishRename();
    });
}
</script>
