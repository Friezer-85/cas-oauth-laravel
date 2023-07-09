<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
  @if (isset($authenticationSuccess))
    <cas:authenticationSuccess>
      <cas:user>{{ $authenticationSuccess['user'] }}</cas:user>
	  @if (isset($authenticationSuccess['attributes']))
        <cas:attributes>
          @foreach ($authenticationSuccess['attributes'] as $key => $value)
            @if (is_array($value))
              @foreach ($value as $value2)
                <cas:{{ $key }}>{{ $value2 }}</cas:{{ $key }}>
              @endforeach
            @else
              <cas:{{ $key }}>{{ $value }}</cas:{{ $key }}>
            @endif
          @endforeach
        </cas:attributes>
	  @endif
    </cas:authenticationSuccess>
  @else
    <cas:authenticationFailure code="{{ $authenticationFailure['code'] }}">{{ $authenticationFailure['description'] }}</cas:authenticationFailure>
  @endif
</cas:serviceResponse>
