import { NavigatorScreenParams } from '@react-navigation/native';
import { AccountAction, Kind } from './lib/api';
export type Tabs = {
  Inicio: undefined;
  Eventos: undefined;
  Foodtrucks: undefined;
  Fotos: undefined;
  Cuenta: undefined;
};
export type RootStack = {
  Principal: NavigatorScreenParams<Tabs> | undefined;
  Detalle: { kind: Kind; contentKey: string };
  CuentaSolicitud: { action: AccountAction };
  ConfiguracionCuenta: undefined;
  SubirFoto: undefined;
};
