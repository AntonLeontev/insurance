<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { useToastsStore } from '@/stores/toasts';

const isOpen = defineModel({ type: Boolean, default: false });

const MAX_FILES = 5;
const MAX_FILE_BYTES = 5 * 1024 * 1024;
const MAX_MESSAGE_LENGTH = 1000;
const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
const ACCEPT = 'image/png,image/jpeg,image/webp,image/gif';

const toastsStore = useToastsStore();

const message = ref('');
const screenshots = ref([]);
const fieldErrors = ref({});
const submitting = ref(false);

const messageErrors = computed(() => fieldErrors.value.message ?? []);
const screenshotErrors = computed(() => {
    const errors = fieldErrors.value.screenshots ?? [];
    const itemErrors = Object.entries(fieldErrors.value)
        .filter(([key]) => key.startsWith('screenshots.'))
        .flatMap(([, value]) => value);

    return [...errors, ...itemErrors];
});

watch(isOpen, (open) => {
    if (open) {
        window.addEventListener('paste', onPaste);
        return;
    }

    window.removeEventListener('paste', onPaste);
    resetForm();
});

onUnmounted(() => {
    window.removeEventListener('paste', onPaste);
});

function resetForm() {
    message.value = '';
    screenshots.value = [];
    fieldErrors.value = {};
    submitting.value = false;
}

function extensionForType(type) {
    if (type === 'image/jpeg') {
        return 'jpg';
    }

    if (type === 'image/webp') {
        return 'webp';
    }

    if (type === 'image/gif') {
        return 'gif';
    }

    return 'png';
}

function validateFile(file, currentCount) {
    if (currentCount >= MAX_FILES) {
        return 'Можно приложить не больше 5 файлов';
    }

    if (!ALLOWED_TYPES.includes(file.type)) {
        return 'Допустимы только изображения PNG, JPG, WEBP или GIF';
    }

    if (file.size > MAX_FILE_BYTES) {
        return `Файл «${file.name}» больше 5 МБ`;
    }

    return null;
}

function addFiles(files, replace = false) {
    const next = replace ? [] : [...screenshots.value];

    for (const file of files) {
        const error = validateFile(file, next.length);
        if (error) {
            toastsStore.addError(error);
            continue;
        }

        next.push(file);
    }

    screenshots.value = next;
}

function onFilesSelected(files) {
    const list = Array.isArray(files) ? files : files ? [files] : [];
    addFiles(list, true);
}

function onPaste(event) {
    if (!isOpen.value) {
        return;
    }

    const items = event.clipboardData?.items;
    if (!items) {
        return;
    }

    const images = [];

    for (const item of items) {
        if (!item.type.startsWith('image/')) {
            continue;
        }

        const blob = item.getAsFile();
        if (!blob) {
            continue;
        }

        const filename = `screenshot-${Date.now()}-${images.length}.${extensionForType(blob.type)}`;
        images.push(new File([blob], filename, { type: blob.type }));
    }

    if (images.length === 0) {
        return;
    }

    event.preventDefault();
    addFiles(images, false);
}

function close() {
    isOpen.value = false;
}

function submit() {
    fieldErrors.value = {};

    if (!message.value.trim()) {
        fieldErrors.value = { message: ['Введите сообщение'] };
        return;
    }

    if (message.value.length > MAX_MESSAGE_LENGTH) {
        fieldErrors.value = {
            message: [`Не больше ${MAX_MESSAGE_LENGTH} символов`],
        };
        return;
    }

    const formData = new FormData();
    formData.append('message', message.value);

    screenshots.value.forEach((file) => {
        formData.append('screenshots[]', file);
    });

    submitting.value = true;

    axios
        .post(route('feedback.store'), formData)
        .then(() => {
            toastsStore.addSuccess('Сообщение отправлено');
            close();
        })
        .catch((error) => {
            if (error.response?.status === 422) {
                fieldErrors.value = error.response.data.errors ?? {};
                return;
            }

            toastsStore.handleResponseError(error);
        })
        .finally(() => {
            submitting.value = false;
        });
}
</script>

<template>
    <v-dialog
        v-model="isOpen"
        max-width="600"
        min-width="400"
        scrollable
        @click:outside="close"
    >
        <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
                <span class="d-flex align-center ga-2">
                    <v-icon icon="mdi-message-text-outline" />
                    Обратная связь
                </span>
                <v-btn
                    icon="mdi-close"
                    variant="plain"
                    @click="close"
                ></v-btn>
            </v-card-title>

            <v-card-text>
                <p class="mb-4">
                    Если хотите предложить улучшение, вам не хватает функционала
                    или что-то не работает, то напишите нам через эту форму
                </p>

                <v-textarea
                    v-model="message"
                    label="Сообщение"
                    variant="outlined"
                    auto-grow
                    rows="4"
                    :maxlength="MAX_MESSAGE_LENGTH"
                    counter
                    :error-messages="messageErrors"
                />

                <v-file-input
                    :model-value="screenshots"
                    :accept="ACCEPT"
                    :error-messages="screenshotErrors"
                    chips
                    counter
                    multiple
                    show-size
                    label="Скриншоты"
                    prepend-icon=""
                    prepend-inner-icon="mdi-paperclip"
                    variant="outlined"
                    hint="До 5 файлов, каждый не больше 5 МБ. Можно вставить из буфера (Ctrl+V)"
                    persistent-hint
                    @update:model-value="onFilesSelected"
                />
            </v-card-text>

            <v-card-actions>
                <v-spacer />
                <v-btn :disabled="submitting" @click="close">Отмена</v-btn>
                <v-btn
                    color="primary"
                    :loading="submitting"
                    :disabled="submitting"
                    @click="submit"
                >
                    Отправить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
