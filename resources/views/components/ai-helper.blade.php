<div
    class="fixed bottom-4 right-4 z-50"
    x-data="{
        open: false,
        loading: false,
        input: '',
        messages: [
            {
                role: 'assistant',
                text: 'Hello, I am your AI Helper. Ask anything about clinic workflows, screens, and testing steps.',
            },
        ],
        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', text });
            this.input = '';
            this.loading = true;

            this.$nextTick(() => {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            });

            try {
                const response = await fetch('{{ route('ai-helper.chat') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    },
                    body: JSON.stringify({ message: text }),
                });

                const payload = await response.json();

                if (!response.ok) {
                    const errorMessage = payload?.message ?? 'Failed to contact AI Helper. Please try again.';
                    this.messages.push({ role: 'assistant', text: errorMessage });
                } else {
                    this.messages.push({ role: 'assistant', text: payload.message ?? 'No response available.' });
                }
            } catch (error) {
                this.messages.push({
                    role: 'assistant',
                    text: 'Network issue while contacting AI Helper. Please try again.',
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                });
            }
        },
    }"
>
    <button
        type="button"
        class="ml-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#00535b] text-white shadow-xl transition hover:scale-105 hover:bg-[#00474d]"
        x-on:click="open = !open"
        aria-label="Toggle AI Helper"
    >
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v8A2.5 2.5 0 0 1 17.5 17H11l-4.5 3v-3H6.5A2.5 2.5 0 0 1 4 14.5v-8Z"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="mt-3 flex h-96 w-80 flex-col overflow-hidden rounded-lg border border-[#bec8ca] bg-white shadow-xl"
        style="display: none;"
    >
        <div class="flex items-center justify-between border-b border-[#e1e3e4] bg-gray-50 px-4 py-3">
            <h3 class="text-sm font-semibold text-[#00535b]">AI Helper</h3>
            <button type="button" class="text-xs text-[#3e494a] hover:text-[#00535b]" x-on:click="open = false">Close</button>
        </div>

        <div x-ref="messages" class="flex-1 space-y-3 overflow-y-auto bg-gray-50 px-3 py-3">
            <template x-for="(message, index) in messages" :key="index">
                <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[85%] rounded-lg px-3 py-2 text-sm leading-5 shadow-sm"
                        :class="message.role === 'user' ? 'bg-[#00535b] text-white' : 'bg-white text-[#191c1d] border border-[#d4d9da]'"
                        x-text="message.text"
                    ></div>
                </div>
            </template>

            <div x-show="loading" class="rounded-lg border border-[#d4d9da] bg-white px-3 py-2 text-xs text-[#6f797a]">
                AI Helper is thinking...
            </div>
        </div>

        <form class="border-t border-[#e1e3e4] bg-white p-3" x-on:submit.prevent="sendMessage()">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    x-model="input"
                    class="w-full rounded-md border border-[#bec8ca] px-3 py-2 text-sm text-[#191c1d] outline-none transition focus:border-[#00535b] focus:ring-2 focus:ring-[#00535b]/20"
                    placeholder="Ask about workflow, screens, or steps..."
                    :disabled="loading"
                >
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-[#00535b] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#00474d] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="loading || !input.trim()"
                >
                    Send
                </button>
            </div>
        </form>
    </div>
</div>
