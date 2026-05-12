Een queue-job heeft alle retries opgebruikt en gefaald.

Job:        {{ $context['job'] ?? 'unknown' }}
Site:       {{ $context['site_id'] ?? '-' }}
Locale:     {{ $context['locale'] ?? '-' }}
User:       {{ $context['user_id'] ?? '-' }}
Attempt:    {{ $context['attempt'] ?? '-' }}
Job UUID:   {{ $context['job_uuid'] ?? '-' }}
Trace hash: {{ $context['trace_hash'] ?? '-' }}

Exception:  {{ $context['exception_class'] ?? '-' }}
Bericht:    {{ $context['exception_message'] ?? '-' }}

@if(! empty($context['exception_file']))
Bestand: {{ $context['exception_file'] }}:{{ $context['exception_line'] ?? '?' }}
@endif

Tip: zoek op het trace-hash in de logs om alle attempts van deze specifieke
fout-shape te vinden. Dezelfde shape op dezelfde dag stuurt geen extra mails
(dedup via dashed__job_failure_log).
