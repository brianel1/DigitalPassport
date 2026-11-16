<script setup lang="ts">
import { ref, watch } from 'vue'
const props=withDefaults(defineProps<{modelValue?:number|string;decimals?:number;min?:number|string;max?:number|string}>(),{decimals:2})
const emit=defineEmits<{'update:modelValue':[value:number|undefined]}>()
const raw=ref(String(props.modelValue??''))
watch(()=>props.modelValue,value=>{if((value??'')!==(raw.value===''?'':Number(raw.value)))raw.value=String(value??'')})
function changed(event:Event){
  const input=event.target as HTMLInputElement,value=input.value
  const pattern=props.decimals===0?/^\d*$/:new RegExp('^\\d*(?:\\.\\d{0,'+props.decimals+'})?$')
  if(!pattern.test(value)){input.value=raw.value;return}
  raw.value=value
  emit('update:modelValue',value===''||value==='.'?undefined:Number(value))
  validate(input)
}
function validate(input:HTMLInputElement){
  const number=Number(raw.value)
  input.setCustomValidity(raw.value && props.min!==undefined && number<Number(props.min)?`Enter at least ${props.min}.`:raw.value && props.max!==undefined && number>Number(props.max)?`Enter ${props.max} or less.`:'')
}
function finish(event:FocusEvent){
  if(raw.value==='.')raw.value=''
  if(raw.value!==''&&props.decimals>0)raw.value=Number(raw.value).toFixed(props.decimals)
  ;(event.target as HTMLInputElement).value=raw.value
  validate(event.target as HTMLInputElement)
}
</script>
<template><input data-slot="input" type="text" :inputmode="decimals===0?'numeric':'decimal'" :value="raw" autocomplete="off" class="h-8 w-full min-w-0 rounded-lg border border-input px-2.5 py-1 text-base outline-none transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm" @input="changed" @blur="finish" /></template>
