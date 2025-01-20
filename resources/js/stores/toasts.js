import { defineStore } from "pinia";
import { ref, reactive } from "vue";

export const useToastsStore = defineStore("toasts", () => {
    const toasts = reactive([]);
    const index = ref(1);

    function addToast(text, type) {
        toasts.push({
            id: index,
            text,
            type,
        });

        index.value++;
    }

    function addInfo(text) {
        addToast(text, "info");
    }
    function addError(text) {
        addToast(text, "error");
    }
    function addSuccess(text) {
        addToast(text, "success");
    }

    function remove(id) {
        const removeIndex = toasts.findIndex((item) => item.id === id);
        if (removeIndex !== -1) {
            toasts.splice(removeIndex, 1);
        }
    }

    return { toasts, addToast, addInfo, addError, addSuccess, remove };
});
