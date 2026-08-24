@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <section class="legal-documents-page padding-top padding-bottom">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="section-header text-center">
                        <span class="subtitle">@lang('Compliance')</span>
                        <h2 class="title">@lang('Legal Documents')</h2>
                        <p>@lang('Click any document to view it in full size.')</p>
                    </div>
                </div>
            </div>

            @if ($documents->isNotEmpty())
                <div class="row gy-4">
                    @foreach ($documents as $document)
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <button class="legal-document-card" type="button" data-document-title="{{ __($document->title) }}"
                                data-document-image="{{ $document->url }}">
                                <span class="legal-document-thumb">
                                    <img src="{{ $document->url }}" alt="{{ __($document->title) }}">
                                </span>
                                <span class="legal-document-content">
                                    <strong>{{ __($document->title) }}</strong>
                                    <small>@lang('Click to preview')</small>
                                </span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center">
                    <h5>@lang('No legal documents found')</h5>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('modal')
    <div class="modal fade legal-document-modal" id="legalDocumentModal" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Legal Document')</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="@lang('Close')"></button>
                </div>
                <div class="modal-body">
                    <img class="legal-document-preview" src="" alt="@lang('Legal Document')">
                </div>
            </div>
        </div>
    </div>
@endpush

@push('style')
    <style>
        .legal-document-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 30, 52, 0.08);
            cursor: pointer;
            display: block;
            overflow: hidden;
            padding: 0;
            text-align: left;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }

        .legal-document-card:hover {
            box-shadow: 0 18px 42px rgba(15, 30, 52, 0.14);
            transform: translateY(-4px);
        }

        .legal-document-thumb {
            background: #f7f7f7;
            display: block;
            height: 230px;
            overflow: hidden;
        }

        .legal-document-thumb img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .legal-document-content {
            display: block;
            padding: 16px;
        }

        .legal-document-content strong,
        .legal-document-content small {
            display: block;
        }

        .legal-document-content small {
            color: #777;
            margin-top: 4px;
        }

        .legal-document-modal .modal-body {
            padding: 12px;
        }

        .legal-document-preview {
            background: #ffffff;
            border-radius: 10px;
            display: block;
            max-height: 78vh;
            object-fit: contain;
            width: 100%;
        }

        @media (max-width: 575px) {
            .legal-document-thumb {
                height: 190px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $('.legal-document-card').on('click', function() {
                var modal = $('#legalDocumentModal');
                modal.find('.modal-title').text($(this).data('document-title'));
                modal.find('.legal-document-preview').attr('src', $(this).data('document-image'));
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
