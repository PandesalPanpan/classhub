<x-filament-panels::page>
    <div
        wire:poll.5000ms="refreshLog"
        x-data="{
            autoScroll: true,
            init() {
                this.$nextTick(() => this.scrollToBottom());
                document.addEventListener('livewire:updated', () => {
                    this.$nextTick(() => {
                        if (this.autoScroll) this.scrollToBottom();
                    });
                });
            },
            onScroll() {
                const el = this.$refs.logContainer;
                this.autoScroll = (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 30);
            },
            scrollToBottom() {
                const el = this.$refs.logContainer;
                if (el) el.scrollTop = el.scrollHeight;
            },
            colorize(content) {
                if (!content || content.trim() === '') {
                    return '<span class=\'text-gray-600\'>(log is empty)</span>';
                }
                const escaped = content
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                return escaped
                    .replace(/\[(EMERGENCY|ALERT|CRITICAL|ERROR)\]/g, '<span class=\'text-red-400 font-semibold\'>[$1]</span>')
                    .replace(/\[(WARNING)\]/g, '<span class=\'text-yellow-400\'>[$1]</span>')
                    .replace(/\[(INFO|NOTICE)\]/g, '<span class=\'text-blue-400\'>[$1]</span>')
                    .replace(/\[(DEBUG)\]/g, '<span class=\'text-gray-500\'>[$1]</span>');
            }
        }"
        x-init="init()"
        class="space-y-4"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show last:</label>
                <select
                    wire:model.live="lineCount"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    <option value="50">50 lines</option>
                    <option value="100">100 lines</option>
                    <option value="200">200 lines</option>
                    <option value="500">500 lines</option>
                </select>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500">
                Auto-refreshes every 5s &bull; Scroll up to pause auto-scroll
            </span>
        </div>

        <pre
            x-ref="logContainer"
            @scroll="onScroll()"
            class="h-[70vh] overflow-y-auto rounded-xl bg-gray-950 p-4 font-mono text-xs leading-relaxed text-gray-300 whitespace-pre-wrap break-all"
            x-html="colorize($wire.logContent)"
        ></pre>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
