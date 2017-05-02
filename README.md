# Test task for IQ Option

## Тестовое задание PHP (Billing): Микросервис баланса пользователей

Приложение хранит в себе идентификаторы пользователей и их баланс. Взаимодействие 
с ним осуществляется исключительно с помощью брокера очередей.

По требованию внешней системы, микросервис может выполнить одну из следующих 
операций со счетом пользователя:

* Списание
* Зачисление
* Блокирование с последующим списанием или разблокированием. Заблокированные 
средства недоступны для использования.
* Перевод от пользователя к пользователю

При проведении любой из этих операций генерируется событие в одну из очередей.

### Основные требования к воркерам

Код воркеров должен безопасно выполняться параллельно в разных процессах
Воркеры могут запускаться одновременно в любом числе экземпляров и выполняться 
произвольное время.
Все операции должны обрабатываться корректно, без двойных списаний.
Будет плюсом покрытие кода юнит-тестами.

### Требования к окружению

Язык программирования: PHP >= 5.5

Можно использовать: любые фреймворки, БД, брокеры очередей, key-value хранилища.


## Основные особенности системы и архитектуры

### Проблема атомарности, согласованности и целостности очереди сообщений 

Исходя из требований задания, наиболее очевидным видится выбор RabbitMQ 
в качестве сервиса очередей. Т.к. он достаточно удобно 
[позволяет реализовать](https://www.rabbitmq.com/tutorials/tutorial-two-php.html) шаблон
[Competing Consumers](http://www.enterpriseintegrationpatterns.com/patterns/messaging/CompetingConsumers.html),
и существует достаточно 
[зрелый и поддерживаемый PHP клиент](https://github.com/php-amqplib/php-amqplib) 
для протокола AMQP.

С другой стороны, брокер RabbitMQ 
[обладает определенным недостатком](https://www.rabbitmq.com/semantics.html) с точки зрения 
[атомарности, согласованности и целостности](https://ru.wikipedia.org/wiki/ACID)
 операций обработки сообщений:
> AMQP does not specify when the effects of transactions should become visible following 
a `tx.commit`, e.g. when published messages will appear in queues and can be consumed 
from other clients, when persistent messages will be written to disk, etc. 
In RabbitMQ the tx.commit-ok indicates that all transaction effects are visible 
and that the broker has accepted responsibility for all the messages published 
in the transaction.

>For acknowledgements, the receipt of a `tx.commit-ok` is an indicator 
that the acknowledgements have been received by the server, 
**not that they have been processed, persisted**, etc. 

Т.е. возможна ситуация, когда брокер сохранит на диск подтверждение об обработке входящего 
(с точки зрения микросервиса) сообщения, но при этом вообще не сохранит исходящее сообщение,
даже если подтверждение об обработке входящего сообщения было отправлено после получения
подтверждения о приеме исходящего сообщения. Таким образом, если в такой момент произойдет
авария, то исходящее сообщение будет потеряно при восстановлении, что видится
недопустимым в рамках финансовой системы (частью которой является микросервис баланса).

Существует несколько способов решения этой проблемы:
1. Использование механизмов кластеризации и дистрибуции брокеров RabbitMQ.
2. Выбор другого сервиса очередей, отвечающего необходимым требованиям 
атомарности, согласованности и целостности.
3. Разработка системы, устойчивой к ошибкам подобного рода.

Кластеризация поможет в случае отказа какой-либо из машин кластера, находящихся в одном
дата-центре. Теоретически возможно использование кластера поверх VPN для машин в разных ДЦ,
но в случае разрыва канала связи, кластер скорее всего распадется на независимые части,
которые проблематично будет синхронизировать в дальнейшем 
(см. пункт [CAP Theorem](https://www.rabbitmq.com/distributed.html)).
Также использование кластера может привести к повышенной нагрузке на каналы связи между
дата-центрами.
Использование же механизмов дистрибуции брокеров не поможет нам в случае отказа одного из ДЦ
целиком, так как дистрибуция работает поверх AMQP и следовательно, подвержена описанной выше
проблеме (атомарности, согласованности и целостности).

Выбор и внедрение другого сервиса очередей может быть достаточно нетривиальной задачей в связи 
с большим объемом требований, которые могут выдвигаться со стороны других сервисов системы.
Либо будет связано необходимостью адаптации большого количества уже работающих сервисов.
Также вполне вероятно, что сервиса, который бы удовлетворял всем требованиям может не существовать
в принципе, а разработка собственного решения может быть достаточно дорогостоящим и рискованным
мероприятием.


### Устойчивая система

Рассмотрим один из вариантов системы устойчивой к потере сообщений. 

Если потри сообщений избежать не удается, то можно ввести в систему сущность-инспектор, 
которая будет следить за прохождением сообщений через элементы системы, т.е. за транзакцией 
внутри системы. Сервис-инициатор транзакции регистрирует ее в инспекторе, а затем может
запрашивать результат выполнения. Сервис, являющийся конечной точкой в жизненном цикле 
транзакции, регистрирует в инспекторе успех выполнения транзакции. В случае, если транзакция
не завершилась за заданное время, то инспектор может повторно ее инициировать, например из
сохраненной в нем копии начального сообщения. Остальные узлы системы в таком случае, 
должны корректно обрабатывать повторные сообщения, т.е. повторная обработка сообщений должна
быть идемпотентной.

Исходя из этого, транзакции в системе могут быть только однонаправленными (невозможен 
откат транзакции) и выполняющимися в смысле best effort. Случай, когда транзакция 
перезапускается в системе больше чем определенное количество раз, является серьезным сбоем
системы, который требует задействования других методов для его исправления.

Возможно так же введение сервиса логирования прохождения транзакций (в т.ч. дубликатов)
через узлы системы, при помощи которого будет возможно выявление узких и сбойных мест.

Таким образом, транзакция должна иметь некий уникальный идентификатор, который позволит
отличать ее от всех остальных, в связи с распределенностью системы для этих целей
логично использовать [UUID](https://ru.wikipedia.org/wiki/UUID).

Обработка дубликатов представляется следующим образом. Каждый узел системы (микросервис)
хранит историю успешно обработанных им сообщений вместе с UUID и телом ответного сообщения. 
В случае получения дубликата, повторная обработка не производится, а отправляется лишь
сохраненный результат. Обработка сообщения совместно с записью результата должна быть атомарной
в рамках узла. История очищается в случае, если за некоторый период времени
отсутствуют незавершенные транзакции. С целью предотвращения коллизий UUID 
(например в случае ошибочной его генерации), узлы системы также могут сохранять 
контрольную сумму входящего сообщения.

### Идеальный вариант

Очевидно, что "серебрянной пули" тут не существует. Все рассмотренные варианты обладают как
определенными плюсами, так и минусами. Наиболее правильным видится использование в каждом
конкретном случае своего решения или какого-то их сочетания.

На начальном этапе развития проекта наиболее предпочтительным все-таки видится использование
механизмов кластеризации брокера. Современные дата-центры даже не очень высокого класса
предоставляют достаточно хорошие гарантии относительно как работоспособности внешних и
внутренних каналов связи, так и снабжения электропитанием, целостности дисковой подсистемы.
Таким образом главной проблемой является простой машин в следствие обслуживания, что
в свою очередь нивелируется использованием кластера.

## Мотивация по поводу выбора архитектурных решений

* Рекомендуется использовать 
[единую точку входа](https://www.rabbitmq.com/clustering.html#clients) 
и не встраивать механизм выбора ноды кластера в клиент.
* Воркеры написаны по принципу [fail-fast](https://en.wikipedia.org/wiki/Fail-fast)
* Сервис спроектирован таким образом, что завершение работы любого из воркеров 
(в т.ч. аварийное) в любое время не выводит систему из консистентного состояния.
Таким образом нет необходимости обрабатывать системные сигналы.
* Ответственность за перезапуск воркеров лежит на внешней системе. 
Например systemd предоставляет 
[достаточные для этого механизмы](https://www.reddit.com/r/systemd/comments/3spd5k/start_multiple_instances_with_one_service_file/).
* Логирование происходит в STDOUT. Что позволяет затем собирать логи любым удобным способом.
Более подробно об этом можно узнать в работе [TwelveFactorApp](https://12factor.net/logs).
* СУБД настроена на максимальное соблюдение требований ACID.
Например [innodb_flush_log_at_trx_commit=1](https://dev.mysql.com/doc/refman/5.7/en/innodb-parameters.html#sysvar_innodb_flush_log_at_trx_commit).
* Структура БД и запросы составлены таким образом, чтобы необходимый уровень изоляции транзакций 
был не выше READ_COMMITTED. Таким образом улучшается паралельность обработки транзакций.
* С целью предупреждения дедлоков в БД, операция перевода средств обрабатывает балансы
в порядке возрастания их идентификаторов.
* Используется кластеризованный брокер RabbitMQ с Durable Mirrored очередями.
Используются подтверждения обработки входящих сообщений и подтвреждение приема исходящих сообщений.
Задействуется [heartbeat](https://www.rabbitmq.com/heartbeats.html).
* Сообщения используют UUID с целью определения дубликатов. 
Сервис хранит хэш входящего сообщения, для предотвращения коллизий UUID (возможно
в следствие ошибок ПО).
* Воркер корректно обрабатывает дублированные сообщения, повторных операций с балансом
по таким сообщениям не происходит. Отправляется результат обработки первого сообщения, 
т.к. без задействования дополнительных систем невозможно достоверно определить отправлялся 
ли ответ до этого. 
Дублирование может возникать как из-за 
[особенностей RabbitMQ](https://www.rabbitmq.com/ha.html#behaviour), 
так из-за падения или остановки воркера до конца обработки сообщения.
* Чтобы избежать лавинного увеличения числа сообщений, вводится дополнительный заголовок сообщения
duplicate_number, увеличивающийся на единицу каждый раз в случае обработки дубликата.
Таким образом потребитель сообщения будет знать, пришел ли дубликат
к нему на его участке ответственности или был переотправлен из-за обработки дубликатов
на предыдущем участке. Таким образом если приходит дубликат с номером больше, чем
тот, что уже был успешно обработан, обработка не производится и результат предыдущей
обработки никуда не отправляется.
* Соединение с СУБД настроено таким образом, чтобы своевременно определять отключение 
клиентов в т.ч. и физическое. Для этих целей возможно использование 
[TCP Keep Alive](http://www.tldp.org/HOWTO/html_single/TCP-Keepalive-HOWTO/#libkeepalive).
Таким образом, чтобы не случилось с воркером, произойдет откат транзакции вместо ее зависания.
Необходимо использование PHP версии >= 5.6.13 из-за 
[неиспользования keep alive](https://bugs.php.net/bug.php?id=70456)
в драйвере mysqlnd. 
Также возможен [тюнинг параметров ядра](http://stackoverflow.com/a/39963632/1511382). 
* Также предполагаем, что идентификаторы пользователей в системе консистентны.
Т.е. пользователь может существовать в системе без наличия записи о балансе.
Т.к. в рамках задания отсутствует операция создания баланса.
