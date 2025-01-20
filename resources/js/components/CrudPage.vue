<template>
	<div class="w-100">
		<div class="mb-4 w-100">
			<slot name="header"></slot>
		</div>
		
		<v-divider class="mb-3" />

		<div class="">
			<slot name="content"></slot>
		</div>
	</div>
</template>
