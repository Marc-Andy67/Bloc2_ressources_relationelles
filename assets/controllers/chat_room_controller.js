import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        currentUser: String
    }

    connect() {
        this.scrollToBottom();
        
        // Use MutationObserver to detect when Turbo/Mercure appends a message
        this.observer = new MutationObserver((mutations) => {
            let shouldScroll = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            shouldScroll = true;
                            
                            // Check if this new message belongs to current user
                            const authorId = node.dataset.authorId;
                            if (authorId && authorId === this.currentUserValue) {
                                // Add classes for the current user's own messages
                                node.classList.add('flex-row-reverse');
                                
                                const bubble = node.querySelector('.msg-bubble');
                                if (bubble) {
                                    bubble.className = "msg-bubble bg-gradient-to-br from-primary to-primary/80 text-white shadow-[0_4px_10px_rgba(var(--color-primary),0.2)] rounded-2xl px-5 py-3 transform transition-all hover:-translate-y-0.5 lg:hover:scale-[1.02]";
                                }
                                
                                const text = node.querySelector('.msg-text');
                                if (text) {
                                    text.classList.remove('text-slate-700', 'dark:text-slate-200');
                                }
                                
                                const wrapper = node.querySelector('.msg-wrapper');
                                if (wrapper) wrapper.classList.add('items-end');
                            }
                        }
                    });
                }
            });
            
            if (shouldScroll) {
                this.scrollToBottom();
            }
        });

        this.observer.observe(this.element, { childList: true });
    }
    
    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    scrollToBottom() {
        this.element.scrollTop = this.element.scrollHeight;
    }
}
