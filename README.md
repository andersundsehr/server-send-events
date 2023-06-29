# erver-send-events package

## install

````sh
composer req andersundsehr/server-send-events
````

## what does it do

it makes it easy to use the `text/event-stream` (Server Send Events).

inside your Controller:

```php
if (ServerSendEventStream::isEventStream($this->request)) {
    $stream = new ServerSendEventStream();
    $trigger = new FileEventTrigger($stream);

    $stopTime = time() + (5 * 60);
    do {
        $stream->sendMessage($this->getInfo()); // is send to the JS long running script

        $trigger->sleepUntilTrigger('changed-' . $currentUser->getUid(), $stopTime);
    } while (time() < $stopTime);
    die();
}
```

somewhere else in the code:
```php
(new FileEventTrigger())->trigger('changed-' . $currentUser->getUid());
```


in your JS
```javascript
//EventSource as an auto restart :)
const evtSource = new EventSource(url, {
  withCredentials: true,
});
evtSource.addEventListener("message", (e) => {
  const data = JSON.parse(e.data);
  // do stuff with the data
  // data comes from $stream->sendMessage()
});
```

# with ♥️ from anders und sehr GmbH

> If something did not work 😮  
> or you appreciate this Extension 🥰 let us know.

> We are hiring https://www.andersundsehr.com/karriere/

