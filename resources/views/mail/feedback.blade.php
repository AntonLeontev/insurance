<x-mail::message>
# Обратная связь

**Имя:** {{ $user->name }}

**Почта:** {{ $user->email }}

## Сообщение

<x-mail::panel>
{{ $feedbackMessage }}
</x-mail::panel>
</x-mail::message>
