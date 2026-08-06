@extends('layouts.admin')

@section('title', 'Kuis | Evomi Admin')

@section('content')
<div x-data="evomiAdminQuiz" class="space-y-6 pb-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900" x-text="t('quiz','title')">Manajemen Kuis</h1>
            <p class="text-gray-500 mt-1" x-text="t('quiz','subtitle')"></p>
        </div>
        <button type="button" x-show="tab === 'questions'" @click="openAdd()" class="flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold shadow-[0_4px_14px_0_rgb(0,0,0,0.1)]">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span x-text="t('quiz','add_question')"></span>
        </button>
    </div>

    <div class="flex gap-2">
        <button type="button" @click="setTab('questions')" class="px-4 py-2 rounded-full text-sm font-semibold transition-colors" :class="tab === 'questions' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200'" x-text="t('quiz','tab_questions')"></button>
        <button type="button" @click="setTab('scores')" class="px-4 py-2 rounded-full text-sm font-semibold transition-colors" :class="tab === 'scores' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200'" x-text="t('quiz','tab_scores')"></button>
    </div>

    <div x-show="loading" class="min-h-[60vh] flex items-center justify-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-gray-900 rounded-full animate-spin"></div></div>
    <div x-show="!loading && error" class="bg-red-50 text-red-700 rounded-2xl px-5 py-4 text-sm border border-red-100" x-text="error"></div>

    {{-- Questions --}}
    <div x-show="!loading && !error && tab === 'questions'" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="search" :placeholder="t('quiz','search_questions')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[720px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="t('quiz','col_question')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('quiz','col_answers')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="filteredItems().length === 0">
                        <tr><td colspan="3" class="px-6 py-12 text-center text-sm text-gray-400" x-text="t('quiz','empty_questions')"></td></tr>
                    </template>
                    <template x-for="q in filteredItems()" :key="q.id">
                        <tr class="hover:bg-gray-50/40">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="localizedQuestionText(q)"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-show="locale !== 'en' && q.question_text_en" x-text="q.question_text_en || ''"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-show="locale === 'en' && q.question_text && q.question_text_en" x-text="q.question_text || ''"></p>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="(q.options || []).length + ' ' + t('quiz','options_word')"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" :title="common().edit" @click="openEdit(q)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click="remove(q.id)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Scores --}}
    <div x-show="!loading && !error && tab === 'scores'" class="admin-table-card">
        <div class="p-5 sm:p-6 border-b border-gray-100">
            <div class="relative w-full max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" x-model="scoreSearch" :placeholder="t('quiz','search_scores')" class="admin-search-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[820px]">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-left" x-text="common().user"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('quiz','col_result')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('quiz','col_score')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="t('quiz','recommended_product')"></th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center" x-text="common().actions"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-if="filteredScores().length === 0">
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400" x-text="t('quiz','empty_scores')"></td></tr>
                    </template>
                    <template x-for="s in filteredScores()" :key="s.id">
                        <tr class="hover:bg-gray-50/40">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900" x-text="s.user?.name || t('quiz','deleted_user')"></p>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="s.user?.email || ''"></p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider border bg-gray-50 text-gray-700 border-gray-200" x-text="personalityLabel(s.dominant_personality)"></span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-900" x-text="(s.match_percentage ?? 0) + '%'"></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600" x-text="s.recommended_product?.title || '-'"></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" class="admin-btn-icon" :title="t('quiz','score_detail_title')" @click="openScoreDetail(s)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon" :title="t('quiz','score_edit_title')" @click="openScoreEdit(s)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                    </button>
                                    <button type="button" class="admin-btn-icon admin-btn-icon--danger" :title="common().delete" @click="removeScore(s.id)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Question modal --}}
<template x-teleport="body">
    <div x-show="modalOpen" x-cloak class="admin-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="closeModal()">
        <div class="admin-modal-panel max-w-3xl h-[min(80vh,52rem)]" role="document" @click.stop>
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="modalMode === 'add' ? t('quiz','modal_add') : t('quiz','modal_edit')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeModal()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="save" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                <label class="block">
                    <span class="admin-field-label" x-text="t('quiz','question_text_id')"></span>
                    <textarea x-model="form.question_text" required rows="2" :placeholder="t('quiz','question_placeholder_id')" class="admin-field-textarea"></textarea>
                </label>
                <label class="block">
                    <span class="admin-field-label" x-text="t('quiz','question_text_en')"></span>
                    <textarea x-model="form.question_text_en" rows="2" :placeholder="t('quiz','question_placeholder_en')" class="admin-field-textarea"></textarea>
                </label>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900" x-text="t('quiz','options_scores_heading')"></h3>
                        <button type="button" class="text-sm font-semibold text-gray-900 hover:underline" @click="addOption()" x-text="'+ ' + t('quiz','add_option')"></button>
                    </div>
                    <template x-for="(opt, i) in form.options" :key="i">
                        <div class="rounded-xl border border-gray-100 p-4 space-y-3 bg-gray-50/40">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('quiz','option_label') + ' ' + (i + 1)"></span>
                                <button
                                    type="button"
                                    class="text-xs font-semibold"
                                    :class="form.options.length > 2 ? 'text-rose-600 hover:underline' : 'text-gray-300 cursor-not-allowed'"
                                    :disabled="form.options.length <= 2"
                                    @click="removeOption(i)"
                                    x-text="common().delete"
                                ></button>
                            </div>
                            <input x-model="opt.option_text" :placeholder="t('quiz','answer_placeholder_id')" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm bg-white">
                            <input x-model="opt.option_text_en" :placeholder="t('quiz','answer_placeholder_en')" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm bg-white">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="block text-[11px] text-gray-500 space-y-1">
                                    <span>Purpose Prestige</span>
                                    <input type="number" min="0" x-model="opt.prestige_score" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm bg-white">
                                </label>
                                <label class="block text-[11px] text-gray-500 space-y-1">
                                    <span>Peaceful Calm</span>
                                    <input type="number" min="0" x-model="opt.peaceful_calm_score" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm bg-white">
                                </label>
                                <label class="block text-[11px] text-gray-500 space-y-1">
                                    <span>Rebel Brave</span>
                                    <input type="number" min="0" x-model="opt.rebel_brave_score" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm bg-white">
                                </label>
                                <label class="block text-[11px] text-gray-500 space-y-1">
                                    <span>Sweet Shy</span>
                                    <input type="number" min="0" x-model="opt.sweet_shy_score" class="w-full h-10 px-3 rounded-xl border border-gray-200 text-sm bg-white">
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeModal()" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="saving ? common().saving : common().save"></button>
                </div>
            </form>
        </div>
    </div>
</template>

    {{-- Score detail modal --}}
<template x-teleport="body">
    <div x-show="scoreDetailOpen" x-cloak class="admin-modal-root" @keydown.escape.window="closeScoreDetail()">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-bold" x-text="t('quiz','score_detail_title')"></h2>
                <button type="button" class="p-2 text-gray-400" @click="closeScoreDetail()">✕</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-4 text-sm">
                <div>
                    <p class="text-base font-bold text-gray-900" x-text="scoreDetail?.user?.name || t('quiz','deleted_user')"></p>
                    <p class="text-gray-500" x-text="scoreDetail?.user?.email || ''"></p>
                </div>
                <dl class="grid grid-cols-2 gap-4">
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('quiz','dominant_result')"></dt><dd class="mt-0.5 font-semibold text-gray-900" x-text="personalityLabel(scoreDetail?.dominant_personality)"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('quiz','recommended_product')"></dt><dd class="mt-0.5 text-gray-900" x-text="scoreDetail?.recommended_product?.title || '-'"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Purpose Prestige</dt><dd class="mt-0.5 text-gray-900" x-text="scoreDetail?.total_prestige ?? 0"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Peaceful Calm</dt><dd class="mt-0.5 text-gray-900" x-text="scoreDetail?.total_peaceful_calm ?? 0"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Rebel Brave</dt><dd class="mt-0.5 text-gray-900" x-text="scoreDetail?.total_rebel_brave ?? 0"></dd></div>
                    <div><dt class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Sweet Shy</dt><dd class="mt-0.5 text-gray-900" x-text="scoreDetail?.total_sweet_shy ?? 0"></dd></div>
                </dl>
                <div x-show="(scoreDetail?.answers || []).length" class="space-y-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400" x-text="t('quiz','user_answers')"></p>
                    <template x-for="a in (scoreDetail?.answers || [])" :key="a.id">
                        <div class="rounded-xl border border-gray-100 px-4 py-3">
                            <p class="font-semibold text-gray-900" x-text="localizedQuestionText(a)"></p>
                            <p class="text-gray-500 mt-0.5" x-text="localizedOptionText(a)"></p>
                        </div>
                    </template>
                </div>
                <div class="flex justify-end pt-1">
                    <button type="button" class="px-4 py-2.5 rounded-xl border text-sm font-semibold" @click="closeScoreDetail()" x-text="common().close"></button>
                </div>
            </div>
        </div>
    </div>
</template>

    {{-- Score edit modal --}}
<template x-teleport="body">
    <div x-show="scoreEditOpen" x-cloak class="admin-modal-root" role="dialog" aria-modal="true" @keydown.escape.window="closeScoreEdit()">
        <div class="admin-modal-panel max-w-lg" role="document" @click.stop>
            <div class="admin-modal-panel__header">
                <h2 class="text-lg font-bold text-gray-900" x-text="t('quiz','score_edit_title')"></h2>
                <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-400" @click="closeScoreEdit()" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="saveScore" class="flex flex-col flex-1 min-h-0">
                <div class="admin-modal-panel__body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="field in personalityFields" :key="field.key">
                        <label class="block">
                            <span class="admin-field-label" x-text="field.label"></span>
                            <input type="number" min="0" x-model="scoreForm[field.key]" class="admin-field-input">
                        </label>
                    </template>
                </div>
                <label class="block">
                    <span class="admin-field-label" x-text="t('quiz','dominant_personality_label')"></span>
                    <select x-model="scoreForm.dominant_personality" class="admin-field-input">
                        <template x-for="p in personalities" :key="p">
                            <option :value="p" x-text="personalityLabel(p)"></option>
                        </template>
                    </select>
                </label>
                </div>
                <div class="admin-modal-panel__footer">
                    <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100" @click="closeScoreEdit()" x-text="common().cancel"></button>
                    <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-semibold disabled:opacity-60" x-text="saving ? common().saving : common().save_changes"></button>
                </div>
            </form>
        </div>
    </div>
</template>
</div>
@endsection