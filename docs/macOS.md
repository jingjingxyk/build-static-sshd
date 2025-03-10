# macOS 环境下构建 swoole-cli

## 构建准备 - 设置默认安装库目录的权限

```shell

sudo mkdir -p /usr/local/swoole-cli
CURRENT_USER=$(whoami) && sudo chown -R ${CURRENT_USER}:staff /usr/local/swoole-cli

```

## macos 环境下构建 完整步骤

```shell

git clone -b main https://github.com/swoole/swoole-cli.git
cd swoole-cli
git submodule update --init -f

bash sapi/quickstart/macos/install-homebrew.sh
bash sapi/quickstart/macos/macos-init.sh

bash setup-php-runtime.sh

__DIR__=$(pwd);
export PATH=${__DIR__}/bin/runtime/:$PATH
alias php="'${__DIR__}/bin/runtime/php -c ${__DIR__}/bin/runtime/php.ini'"

composer install  --no-interaction --no-autoloader --no-scripts --profile --no-dev
composer dump-autoload --optimize --profile --no-dev

php prepare.php --without-docker=1  +apcu +ds +xlswriter +ssh2 +uuid

bash make-install-deps.sh

# 静态编译依赖库
bash make.sh  all-library

# 静态编译 PHP 预处理
bash make.sh config

# 静态编译PHP （编译、汇编、链接）
bash make.sh build

# 静态编译PHP （打包）
bash make.sh archive


./bin/swoole-cli -m
./bin/swoole-cli --ri swoole
xattr -cr ./bin/swoole-cli
otool -L ./bin/swoole-cli


```

## 可使中国大陆软件镜像源命令脚本

```shell

sh sapi/quickstart/macos/install-homebrew.sh  --mirror china

sh sapi/quickstart/macos/macos-init.sh  --mirror china

# 准备PHP 运行时 使用镜像 （镜像源 https://www.swoole.com/download）
bash setup-php-runtime.sh --mirror china


```

## 可使用代理的命令脚本

```bash

# 准备PHP 运行时 使用代理
bash setup-php-runtime.sh --proxy http://192.168.3.26:8015

php prepare.php --without-docker=1 +apcu +ds +xlswriter +ssh2 +uuid --with-http-proxy=socks5h://127.0.0.1:2000

```

## 准备依赖库源码

> 源码来源: https://github.com/swoole/swoole-cli/releases/download/${TAG}/all-deps.zip

```bash

bash sapi/download-box/download-box-get-archive-from-server.sh

bash sapi/download-box/download-box-get-archive-from-server.sh --mirror china

```

## 构建步骤简述

0. 清理 `brew` 安装的软件
1. 执行 `php prepare.php --without-docker=1 @macos` 生成构建shell 脚本
2. 编译所有依赖的库 `./make.sh all-library`
3. 配置 `./make.sh config`
4. 构建 `./make.sh build`

## 清理

使用 `brew` 安装的库可能会干扰 `swoole-cli` 的编译，必须要在构建之前将关联的软件进行卸载。在构建完成后再重新安装。

```shell

# 多数情况下，只需要卸载  snappy 和 capstone

# brew uninstall --ignore-dependencies oniguruma
# brew uninstall --ignore-dependencies brotli
# brew uninstall --ignore-dependencies freetype
# brew uninstall --ignore-dependencies zstd

brew uninstall --ignore-dependencies snappy
brew uninstall --ignore-dependencies capstone

```

## 安装必要的软件 和 配置环境变量

```shell

brew install  wget curl  libtool automake  re2c llvm flex bison

brew install  gettext coreutils binutils libunistring

HOMEBREW_PREFIX=$(brew --prefix)

export PATH=${HOMEBREW_PREFIX}/opt/bison/bin:${HOMEBREW_PREFIX}/opt/llvm/bin:$PATH

```

# 问题

## 缺少 bison

下载源代码，自行编译安装
(此问题已解决，安装依赖库时 已经包含bison源码编译,或者如下操作)

```shell
    brew intall bison

    export PATH=/usr/local/opt/bison/bin:$PATH

```

## llvm 连接器 ld64.lld 、 lld 找不到

```shell
    # 若目录不存在，可以先安装 llvm
    brew intall llvm

    export PATH=/usr/local/opt/llvm/bin:$PATH

```

## 缺少`libtool`

可使用 `which glibtool` 找到所在路径，使用 `ln -s` 创建软连接

```shell
ln -s /usr/local/bin/glibtool /usr/local/bin/libtool
ln -s /usr/local/bin/glibtoolize /usr/local/bin/libtoolize
```

若使用 `brew` 安装，可能是在 `/opt/homebrew/bin/glibtool` 位置

```shell
ln -s /opt/homebrew/bin/glibtool /opt/homebrew/bin/libtool
ln -s /opt/homebrew/bin/glibtoolize /opt/homebrew/bin/libtoolize
```

## 缺少`gettext coreutils re2c flex bison`

```shell

 brew install gettext coreutils re2c libunistring flex bison

```

## curl configure 检测不通过

修改 `ext/curl/config.m4` ，去掉 `HAVE_CURL` 检测

## `icu/oniguruma` 找不到

错误信息：

```
checking for icu-uc >= 50.1 icu-io icu-i18n... no
configure: error: Package requirements (icu-uc >= 50.1 icu-io icu-i18n) were not met:

No package 'icu-uc' found
No package 'icu-io' found
No package 'icu-i18n' found
```

### 1. 需要手工执行 `export PKG_CONFIG_PATH` 设置路径(复制 `make.sh` 中的指令)

### 2. 设置 `ICU` 相关环境变量

```shell
export ICU_CFLAGS=$(pkg-config --cflags icu-uc)
export ICU_LIBS=$(pkg-config --libs icu-uc)
export ONIG_CFLAGS=$(pkg-config --cflags oniguruma)
export ONIG_LIBS=$(pkg-config --libs oniguruma)
export LIBZIP_CFLAGS=$(pkg-config --cflags libzip)
export LIBZIP_LIBS=$(pkg-config --libs libzip)
export LIBSODIUM_CFLAGS=$(pkg-config --cflags libsodium)
export LIBSODIUM_LIBS=$(pkg-config --libs libsodium)
```

## 下载 macOS 版本 运行无权限，解决方案

> Mac安装应用“提示文件已损坏”或“来自身份不明开发者”解决方法

> note: macos clearing the com.apple.quarantine extended attribute
> macos环境下 首次运行提示无权限 ，通过清除扩展属性 解决

```bash

# 查看扩展属性
xattr ./bin/swoole-cli
# 移除扩展属性
sudo xattr -cr ./swoole-cli
sudo xattr -d com.apple.quarantine  ./bin/swoole-cli

file ./bin/swoole-cli
otool -L ./bin/swoole-cli

```

## [macOS doesn't officially support fully static linking ](https://developer.apple.com/library/archive/qa/qa1118/_index.html)

macos 支持构建静态库，不支持构建静态链接的二进制文件
