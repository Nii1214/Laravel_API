FROM nginx:alpine

# デフォルトの設定を削除
RUN rm /etc/nginx/conf.d/default.conf

# カスタム設定ファイルをコピー
COPY nginx.conf /etc/nginx/conf.d/ 