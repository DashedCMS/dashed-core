@if(!isset($page) || (isset($page) && !$page->is_home))
    @if($breadcrumbs ?? false)
        <nav class="py-4 text-sm xl:rounded-md bg-white">
            <x-container>
                <ul class="flex items-center gap-2 flex-wrap">
                    @foreach ($breadcrumbs as $breadcrumb)
                        @if ($loop->last)
                            <li>
                                <a class="text-primary" href="{{$breadcrumb['url']}}">{{$breadcrumb['name']}}</a>
                            </li>
                        @else
                            <li class="after:content-['/'] after:ml-1 after:text-black/30">
                                <a href="{{$breadcrumb['url']}}">{{$breadcrumb['name']}}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </x-container>
        </nav>
    @endif
@endif
