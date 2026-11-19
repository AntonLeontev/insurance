import axios from 'axios';
import { shallowRef, toValue, watch } from 'vue';
import { useToastsStore } from '@/stores/toasts';

export function useDashboardStats(agencyId) {
    const stats = shallowRef(null);
    const loading = shallowRef(false);
    const error = shallowRef(null);
    const toastsStore = useToastsStore();
    let requestSeq = 0;

    watch(() => toValue(agencyId), async (id) => {
        const currentRequest = ++requestSeq;

        if (!id) {
            stats.value = null;
            loading.value = false;
            error.value = null;

            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(route('dashboard'), {
                params: { agency_id: id },
            });

            if (currentRequest !== requestSeq) {
                return;
            }

            stats.value = response.data;
        } catch (requestError) {
            if (currentRequest !== requestSeq) {
                return;
            }

            stats.value = null;
            error.value = requestError;
            toastsStore.handleResponseError(requestError);
        } finally {
            if (currentRequest === requestSeq) {
                loading.value = false;
            }
        }
    }, { immediate: true });

    return { stats, loading, error };
}
