import { defineStore } from "pinia";
import { ref, reactive } from "vue";

export const useToastsStore = defineStore("toasts", () => {
    const toasts = reactive([]);
    let index = 1;

    function addToast(text, type, delay = 0) {
        toasts.push({
            id: index,
            text,
            type,
        });

        index++;

        if (delay > 0) {
            setTimeout(() => {
                remove(index - 1);
            }, delay);
        }
    }

    function addInfo(text, delay = 0) {
        addToast(text, "info", delay);
    }
    function addError(text, delay = 0) {
        addToast(text, "error", delay);
    }
    function addSuccess(text, delay = 0) {
        addToast(text, "success", delay);
    }

    function remove(id) {
        const removeIndex = toasts.findIndex((item) => item.id === id);
        if (removeIndex !== -1) {
            toasts.splice(removeIndex, 1);
        }
    }

	function handleResponseError(error) {
        if (error.response?.status === 422) {
            return;
        }

        addError(
            error.response?.data?.message ?? error.message ?? "Произошла ошибка"
        );
    }

    return {
        toasts,
        addToast,
        addInfo,
        addError,
        addSuccess,
        remove,
        handleResponseError,
    };
});
