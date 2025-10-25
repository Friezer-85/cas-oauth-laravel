<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
  @if (isset($authenticationSuccess))
    <cas:authenticationSuccess>
      <cas:user>{{ $authenticationSuccess['user'] }}</cas:user>
	  @if (isset($authenticationSuccess['attributes']))
        <cas:attributes>
          @foreach ($authenticationSuccess['attributes'] as $key => $value)
            @php $cleanKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key); @endphp
            @if (is_array($value))
              @foreach ($value as $value2)
                <cas:{{ $cleanKey }}>{{ $value2 }}</cas:{{ $cleanKey }}>
              @endforeach
            @else
              <cas:{{ $cleanKey }}>{{ $value }}</cas:{{ $cleanKey }}>
            @endif
          @endforeach
        </cas:attributes>
	  @endif
    </cas:authenticationSuccess>
  @else
    <cas:authenticationFailure code="{{ $authenticationFailure['code'] }}">{{ $authenticationFailure['description'] }}</cas:authenticationFailure>
  @endif
</cas:serviceResponse>